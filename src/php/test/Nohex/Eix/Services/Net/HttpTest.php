<?php
/**
 * Unit test for class Nohex\Eix\Services\Net\Http.
 */

namespace Nohex\Eix\Services\Net;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Services\Net\Http;

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

    public function testDefaultConstructor()
    {
        $settings = (object) array(
            'host' => 'eix.test',
        );

        $httpClient = new Http($settings);

        $this->assertTrue($httpClient instanceof Http);
    }

}
