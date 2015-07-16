<?php
/**
 * Unit test for class Eix\Core\Responders\Http\Identity.
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Responders\Http\Identity as IdentityResponder;

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new IdentityResponder;

        $this->assertTrue($responder instanceof IdentityResponder);
    }

}
