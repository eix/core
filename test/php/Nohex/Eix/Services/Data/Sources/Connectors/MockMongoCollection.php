<?php

namespace Nohex\Eix\Services\Data\Sources\Connectors;

class MockMongoCollection
{
    public function selectCollection($collectionName)
    {
        return $collectionName;
    }
}
