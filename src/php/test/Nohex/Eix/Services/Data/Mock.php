<?php

namespace Nohex\Eix\Services\Data;

use Nohex\Eix\Services\Data\Factory;
use Nohex\Eix\Services\Data\Sources\Mock as MockDataSource;

class Mock extends Factory
{
    protected function getDefaultDataSource()
    {
        return MockDataSource::getInstance();
    }
}
