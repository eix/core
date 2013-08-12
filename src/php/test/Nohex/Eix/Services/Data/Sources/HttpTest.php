<?php
/**
 * Unit test for class Nohex\Eix\Services\Data\Sources\Http.
 */

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Services\Data\Sources\Http as HttpDataSource;
use Nohex\Eix\Services\Net\Http\Mock as MockHttpClient;

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
