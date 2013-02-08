<?php
/**
 * Unit test for class Nohex\Eix\Core\Requests\Http.
 */

namespace Nohex\Eix\Core\Requests;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http as HttpRequest;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    private static $application;
    private static $data;

    protected function setUp()
    {
        self::$data = array(
            'key' => 'value',
        );

        self::$application = new MockApplication;
    }

    protected function tearDown()
    {
        self::$data = null;
        self::$application = null;
    }

    public function testGetContentTypeToken()
    {
        $this->assertEquals(
            HttpRequest::getContentTypeToken('application/json'),
            'Json'
        );
    }

}
