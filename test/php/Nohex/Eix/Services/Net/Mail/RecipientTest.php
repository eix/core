<?php
/**
 * Unit test for class Nohex\Eix\Services\Net\Mail\Recipient.
 */

namespace Nohex\Eix\Services\Net\Mail;

use Nohex\Eix\Services\Net\Mail\Recipient;

class RecipientTest extends \PHPUnit_Framework_TestCase
{
    const TO_TYPE = 'to';
    const BAD_TYPE = 'bad';
    const GOOD_EMAIL = 'good@email.com';
    const BAD_EMAIL = 'bad@email.';
    const NAME = 'Email Name';

    public function testDefaultConstructor()
    {
        $recipient = new Recipient(self::TO_TYPE, self::GOOD_EMAIL, self::NAME);

        $this->assertEquals($recipient->getType(), self::TO_TYPE);
    }

    /**
     * @expectedException Nohex\Eix\Services\Net\Mail\Exception
     */
    public function testDefaultConstructorWithWrongType()
    {
        $recipient = new Recipient(self::BAD_TYPE, self::GOOD_EMAIL);
    }

}
