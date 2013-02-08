<?php
/**
 * Unit test for class Nohex\Eix\Core\Responders\Http\Identity.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Core\Responders\Http\Identity as IdentityResponder;

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new IdentityResponder;

        $this->assertTrue($responder instanceof IdentityResponder);
    }

}
