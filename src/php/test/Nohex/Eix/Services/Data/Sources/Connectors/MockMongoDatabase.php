<?php

namespace Nohex\Eix\Services\Data\Sources\Connectors;

use Nohex\Eix\Services\Data\Sources\Connectors\MockMongoCollection;

class MockMongoDatabase
{
    private $returns = array();

    public function selectCollection($collectionName)
    {
        return new MockMongoCollection;
    }
}
