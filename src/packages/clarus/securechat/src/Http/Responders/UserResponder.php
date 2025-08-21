<?php

namespace Clarus\SecureChat\Http\Responders;

use Clarus\SecureChat\Http\Transformers\UserTransformer;

class UserResponder extends BaseResponder
{
    protected $collectionKey = 'users';

    protected $itemKey = 'user';

    /**
     * @param  mixed  $parentRecord
     * @return UserTransformer
     */
    protected function getTransformer($parentRecord = null)
    {
        return new UserTransformer($parentRecord);
    }
}
