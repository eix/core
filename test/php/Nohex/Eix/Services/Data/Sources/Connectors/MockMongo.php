<?php

namespace Nohex\Eix\Services\Data\Sources\Connectors;

use Nohex\Eix\Services\Data\Sources\Connectors\MockMongoCollection;

class MockMongo
{
    public function selectDB()
    {
        return new MockMongoCollection;
    }
}
