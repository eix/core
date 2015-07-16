<?php
/**
 * Unit test for class Eix\Services\Data\Factory.
 */

namespace Eix\Services\Data;

use Eix\Services\Data\Mock as MockFactory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSingleton()
    {
        $factory1 = MockFactory::getInstance();
        $factory2 = MockFactory::getInstance();

        $this->assertTrue($factory1 instanceof MockFactory);
        $this->assertEquals($factory1, $factory2);
    }
}
