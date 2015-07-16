<?php

namespace Eix\Services\Data\Sources\Connectors;

use Eix\Services\Data\Sources\Connectors\MockMongoCollection;

class MockMongoDatabase
{
    private $returns = array();

    public function selectCollection($collectionName)
    {
        return new MockMongoCollection;
    }
}
