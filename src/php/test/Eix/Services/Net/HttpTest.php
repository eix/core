<?php
/**
 * Unit test for class Eix\Services\Net\Http.
 */

namespace Eix\Services\Net;

use Eix\Services\Net\Http;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    private static $data;

    protected function setUp()
    {
        self::$data = array(
            'key' => 'value',
        );
    }

    protected function tearDown()
    {
        self::$data = null;
    }

    public function testDefaultConstructor()
    {
        $settings = (object) array(
            'host' => 'eix.test',
        );

        $httpClient = new Http($settings);

        $this->assertTrue($httpClient instanceof Http);
    }

}
