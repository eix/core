<?php

namespace Eix\Services\Data;

use Eix\Services\Data\Factory;
use Eix\Services\Data\Sources\Mock as MockDataSource;

class Mock extends Factory
{
    protected function getDefaultDataSource()
    {
        return MockDataSource::getInstance();
    }
}
