<?php

namespace Nohex\Eix\Services\Data\Sources\Connectors;

class MockMongo
{
    public function selectDB()
    {
        return new MockMongoDatabase;
    }
}
