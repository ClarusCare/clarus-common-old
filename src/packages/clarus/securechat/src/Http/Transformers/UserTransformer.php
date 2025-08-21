<?php

namespace Clarus\SecureChat\Http\Transformers;

use App\Models\User;
use Clarus\SecureChat\Models\ChatRoom;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    protected $parentRecord;

    /**
     * UserTransformer constructor.
     *
     * @param  mixed  $parentRecord
     */
    public function __construct($parentRecord = null)
    {
        $this->parentRecord = $parentRecord;
    }

    public function transform(User $user)
    {
        $partners = [];

        foreach ($user->partners as $partner) {
            if ($partner->active == true) {
                $partners[] = $partner->id;
            }
        }

        foreach ($user->providers as $provider) {
            foreach ($provider->partnerProviders->where('active', true) as $partnerProvider) {
                if (! in_array($partnerProvider->partner_id, $partners)) {
                    $partners[] = $partnerProvider->partner_id;
                }
            }
        }

        $data = [
            'id'                    => (int) $user->id,
            'first_name'            => $user->first_name,
            'last_name'             => $user->last_name,
            'email'                 => $user->email,
            'full_name'             => $user->full_name,
            'chat_status'           => $user->chat_status,
            'profile_image'         => $user->profile_image,
            'profile_image_retina'  => $user->profile_image_retina,
            'created_at'            => $user->created_at->toDateTimeString(),
            'updated_at'            => $user->updated_at->toDateTimeString(),
            'partners'              => $partners,
        ];

        $parent = $this->parentRecord;
        if ($parent && $parent instanceof ChatRoom) {
            $data['active_in_room'] = $parent->isUserActive($user);
        }

        return $data;
    }
}
