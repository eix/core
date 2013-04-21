<?php

namespace Nohex\Eix\Services\Data\Sources\Connectors;

class MockMongoResult extends \ArrayIterator
{
    public function sort()
    {
        return $this->ksort();
    }
}
