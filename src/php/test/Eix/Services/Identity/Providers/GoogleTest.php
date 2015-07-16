<?php
/**
 * Unit test for class Eix\Services\Identity\Providers\Google.
 */

namespace Eix\Services\Identity\Providers;

use Eix\Services\Identity\Providers\Google;
use Eix\Services\Identity\Providers\OpenId;
use Eix\Services\Identity\Providers\Consumers\Mock as MockConsumer;

class GoogleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        Google::setConsumer(new MockConsumer);
        $identityProvider = new Google;

        $this->assertTrue($identityProvider instanceof OpenId);
    }

}
