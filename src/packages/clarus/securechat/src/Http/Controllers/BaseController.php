<?php

namespace Clarus\SecureChat\Http\Controllers;

use ReflectionClass;
use Illuminate\Routing\Controller;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    /**
     * @param  null  $message
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function deleteResponse($message = null)
    {
        return response([$message ?: 'deleted'], 204);
    }

    /**
     * @param $errorCode
     * @param $name
     * @param $description
     * @param  array  $optionalData
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function errorResponse($errorCode, $name, $description, ?array $optionalData = null)
    {
        $content = [
            'error'             => $name,
            'error_description' => $description,
        ];

        if ($optionalData) {
            $content = array_merge($content, $optionalData);
        }

        return response($content, $errorCode);
    }

    /**
     * @param  MessageBag  $errors
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function failedValidationResponse(MessageBag $errors)
    {
        return response(array_merge([
            'error'             => 'failed_validation',
            'error_description' => 'One or more fields failed validation.',

        ], $errors->getMessages()), 422);
    }

    /**
     * @param $model
     * @param $modelName
     * @return array
     */
    protected function metaDataArray($model, $modelName)
    {
        if ($model instanceof Collection || $model instanceof LengthAwarePaginator || $model instanceof Paginator) {
            return [
                'meta'          => [
                    'total'        => $model->total(),
                    'per_page'     => $model->perPage(),
                    'current_page' => $model->currentPage(),
                    'last_page'    => $model->lastPage(),
                    'from'         => $model->firstItem(),
                    'to'           => $model->lastItem(),
                ],
                $modelName => $model->getCollection()->toArray(),
            ];
        }

        return [
            $modelName => $model->toArray(),
        ];
    }

    /**
     * Pull nested data out of input array by model name.
     *
     * @param $input
     * @param $modelName
     * @return mixed
     */
    protected function parseJsonApiInput($input, $modelName)
    {
        if (isset($input[$modelName])) {
            return $input[$modelName];
        }

        return $input;
    }

    /**
     * @param  null  $message
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function successResponse($message = null)
    {
        return response([
            'status'  => $message ?: 'success',
            'success' => true,
        ], 200);
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    protected function unauthorizedResponse()
    {
        return response([
            'error'             => 'unauthorized',
            'error_description' => 'Unauthorized access.',
        ], 403);
    }

    /**
     * Does user belong to a chat room?
     *
     * @param $user
     * @param $chatRoomID
     * @return mixed
     */
    protected function userBelongsToRoom($user, $chatRoomID)
    {
        return $user->chatRooms()->where('chat_rooms.id', $chatRoomID)->first();
    }

    /**
     * Does user own this chat room?
     *
     * @param $user
     * @param $chatRoomID
     * @return mixed
     */
    protected function userOwnsRoom($user, $chatRoomID)
    {
        return $user->ownedChatRooms()->where('id', $chatRoomID)->first();
    }

    /**
     * Does one user belong to the same partner as another?
     *
     * @param $user
     * @param $anotherUser
     * @return mixed
     */
    protected function usersBelongToSamePartner($user, $anotherUser)
    {
        return (bool) array_intersect($this->usersPartnerIDs($user), $this->usersPartnerIDs($anotherUser));
    }

    /**
     * @param $user
     * @return array
     */
    protected function usersPartnerIDs($user)
    {
        $providerPartners = [];
        foreach ($user->providers as $provider) {
            $providerPartners = array_merge($providerPartners, $provider->partnerProviders()->active()->pluck('partner_id')->toArray());
        }

        return array_merge($user->partners()->active()->pluck('partner_id')->toArray(), $providerPartners);
    }

    /**
     * Validates input; returns errors if present.
     *
     * @param $input
     * @param $rules
     * @return mixed
     */
    protected function validate($input, $rules)
    {
        $validator = Validator::make($input, $rules);

        return $validator->fails() ? $validator->messages() : null;
    }

    /**
     * @param $value
     * @param $class
     * @return bool
     */
    protected function valueInClassConstants($value, $class)
    {
        $reflector = new ReflectionClass($class);
        $constants = $reflector->getConstants();

        return in_array($value, $constants);
    }
}
