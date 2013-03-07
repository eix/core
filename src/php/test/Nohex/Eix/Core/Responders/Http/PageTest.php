<?php
/**
 * Unit test for class Nohex\Eix\Core\Responders\Http\Page.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Core\Responders\Http\Page as PageResponder;

class PageTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new PageResponder;

        $this->assertTrue($responder instanceof PageResponder);
    }

}
