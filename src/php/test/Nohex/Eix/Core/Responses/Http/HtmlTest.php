<?php
/**
 * Unit test for class Nohex\Eix\Core\Responses\Http\Html.
 */

namespace Nohex\Eix\Core\Responses\Http;

use Nohex\Eix\Core\Responses\Http\Html as HtmlResponse;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    private static $data;

    protected function setUp()
    {
        self::$data = array(
            'key' => 'value',
        );
    }

    public function testDefaultConstructor()
    {
        $htmlResponse = new HtmlResponse;
        $htmlResponse->setData('data', self::$data);
        $htmlResponse->setTemplateId('template');

        $this->assertTrue($htmlResponse instanceof HtmlResponse);
    }

    protected function tearDown()
    {
        self::$data = null;
    }

}
