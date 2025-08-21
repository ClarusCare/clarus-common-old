<?php

namespace Clarus\SecureChat\Gateways;

class BaseGateway
{
    protected function pluckFromCollection($collection, $value)
    {
        return collect(Arr::pluck($collection->all(), $value, null));
    }

    protected function uniqueByKey($collection, $key)
    {
        $key = $this->valueRetriever($key);
        $exists = [];

        return $collection->reject(function ($item) use ($key, &$exists) {
            if (in_array($id = $key($item), $exists, false)) {
                return true;
            }
            $exists[] = $id;
        });
    }

    protected function valueRetriever($value)
    {
        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }
}
