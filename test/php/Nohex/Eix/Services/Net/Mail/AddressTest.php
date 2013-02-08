<?php
/**
 * Unit test for class Nohex\Eix\Services\Net\Mail\Address.
 */

namespace Nohex\Eix\Services\Net\Mail;

use Nohex\Eix\Services\Net\Mail\Address;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    const GOOD_EMAIL = 'good@email.com';
    const BAD_EMAIL = 'bad@email.';
    const NAME = 'Email Name';

    /**
     * @expectedException Nohex\Eix\Services\Net\Mail\Exception
     */
    public function testDefaultConstructorWithWrongAddress()
    {
        $address = new Address(self::BAD_EMAIL, self::NAME);
    }

    public function testAddress()
    {
        $address = new Address(self::GOOD_EMAIL, self::NAME);

        $this->assertEquals($address->getAddress(), self::GOOD_EMAIL);
    }

    public function testName()
    {
        $address = new Address(self::GOOD_EMAIL, self::NAME);

        $this->assertEquals($address->getName(), self::NAME);
    }

}
