<?php

namespace Clarus\SecureChat\Http\Responders;

use stdClass;
use App\Models\User;
use League\Fractal\Manager;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\ArraySerializer;

abstract class BaseResponder
{
    protected $collectionKey;

    /**
     * @var \League\Fractal\Manager
     */
    protected $fractalManager;

    protected $itemKey;

    public function __construct(Manager $fractalManager, ArraySerializer $serializer)
    {
        $fractalManager->setSerializer($serializer);
        $this->fractalManager = $fractalManager;
    }

    public function createCollectionResponse($collection, $parentRecord = null)
    {
        $resource = new Collection($collection, $this->getTransformer($parentRecord), $this->collectionKey);

        $responseData = $this->fractalManager->createData($resource)->toArray();
        $responseData['unread_counts'] = $this->getUnreadCountDataForUser(Auth::user());

        return new JsonResponse($responseData, 200);
    }

    public function createItemResponse($item, $parentRecord = null)
    {
        $resource = new Item($item, $this->getTransformer($parentRecord), $this->itemKey);

        $responseData = $this->fractalManager->createData($resource)->toArray();
        $responseData['unread_counts'] = $this->getUnreadCountDataForUser(Auth::user());

        return new JsonResponse($responseData, 200);
    }

    public function successResponse($message = null)
    {
        $responseData = [
            'status'        => $message ?: 'success',
            'success'       => true,
            'unread_counts' => $this->getUnreadCountDataForUser(Auth::user()),
        ];

        return new JsonResponse($responseData, 200);
    }

    abstract protected function getTransformer($parentRecord = null);

    protected function getUnreadCountDataForUser(User $user)
    {
        $total = 0;
        $partners = [];
        $rooms = [];

        foreach ($user->chatRooms as $room) {
            $count = $user->unreadChatMessagesForChatRoom($room)->count();
            $total += $count;

            $rooms[$room->id] = $count;
            if (array_key_exists($room->partner_id, $partners)) {
                $partners[$room->partner_id] += $count;
            } else {
                $partners[$room->partner_id] = $count;
            }
        }

        return [
            'total'    => $total,
            'partners' => count($partners) > 0 ? $partners : new stdClass(),
            'rooms'    => count($rooms) > 0 ? $rooms : new stdClass(),
        ];
    }
}
