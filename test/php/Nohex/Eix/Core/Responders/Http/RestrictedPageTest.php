<?php
/**
 * Unit test for class Nohex\Eix\Core\Responders\Http\RestrictedPage.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Core\Responders\Http\RestrictedPage as RestrictedPageResponder;

class RestrictedPageTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new RestrictedPageResponder;

        $this->assertTrue($responder instanceof RestrictedPageResponder);
    }

}
