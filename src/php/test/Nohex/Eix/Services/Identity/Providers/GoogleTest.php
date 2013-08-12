<?php
/**
 * Unit test for class Nohex\Eix\Services\Identity\Providers\Google.
 */

namespace Nohex\Eix\Services\Identity\Providers;

use Nohex\Eix\Services\Identity\Providers\Google;
use Nohex\Eix\Services\Identity\Providers\OpenId;
use Nohex\Eix\Services\Identity\Providers\Consumers\Mock as MockConsumer;

class GoogleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        Google::setConsumer(new MockConsumer);
        $identityProvider = new Google;

        $this->assertTrue($identityProvider instanceof OpenId);
    }

}
