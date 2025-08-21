<?php

namespace Clarus\SecureChat\Http\Responders;

use Clarus\SecureChat\Http\Transformers\PartnerTransformer;

class PartnerResponder extends BaseResponder
{
    protected $collectionKey = 'partners';

    protected $itemKey = 'partner';

    /**
     * @param  mixed  $parentRecord
     * @return PartnerTransformer
     */
    protected function getTransformer($parentRecord = null)
    {
        return new PartnerTransformer();
    }
}
