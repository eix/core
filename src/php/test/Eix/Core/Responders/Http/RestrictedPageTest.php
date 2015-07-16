<?php
/**
 * Unit test for class Eix\Core\Responders\Http\RestrictedPage.
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Responders\Http\RestrictedPage as RestrictedPageResponder;

class RestrictedPageTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new RestrictedPageResponder;

        $this->assertTrue($responder instanceof RestrictedPageResponder);
    }

}
