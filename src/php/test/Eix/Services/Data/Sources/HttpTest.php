<?php
/**
 * Unit test for class Eix\Services\Data\Sources\Http.
 */

namespace Eix\Services\Data\Sources;

use Eix\Core\MockApplication;
use Eix\Services\Data\Sources\Http as HttpDataSource;
use Eix\Services\Net\Http\Mock as MockHttpClient;

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
        $httpClient = new MockHttpClient($settings);
        HttpDataSource::setHttpClient($httpClient);
        $dataSource = new HttpDataSource;

        $this->assertTrue($dataSource instanceof HttpDataSource);
    }

}
