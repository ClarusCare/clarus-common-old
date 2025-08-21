<?php

namespace Clarus\SecureChat\Http\Transformers;

use App\Models\Partner;
use League\Fractal\TransformerAbstract;

class PartnerTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(Partner $partner)
    {
        $users = $partner->users->map(function ($user) {
            return (new UserTransformer())->transform($user);
        });

        return [
            'id'                    => $partner->id,
            'name'                  => $partner->name,
            'timezone'              => $partner->timezone,
            'enable_mobile_beta'    => true,
            'enable_secure_chat'    => $partner->hasChat(),
            'enable_paging'         => $partner->hasPaging(),
            'unread_message_count'  => $partner->unread_message_count,
            'created_at'            => $partner->created_at->toDateTimeString(),
            'updated_at'            => $partner->updated_at->toDateTimeString(),
            'users'                 => $users,
        ];
    }
}
