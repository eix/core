<?php
/**
 * Unit test for class Eix\Core\Responders\Http\Page.
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Responders\Http\Page as PageResponder;

class PageTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $responder = new PageResponder;

        $this->assertTrue($responder instanceof PageResponder);
    }

}
