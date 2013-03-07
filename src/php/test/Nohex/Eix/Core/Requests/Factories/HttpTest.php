<?php
/**
 * Unit test for class Nohex\Eix\Core\Requests\Factories\Http.
 */

namespace Nohex\Eix\Core\Requests\Factories;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Core\Requests\Http as HttpRequest;
use Nohex\Eix\Core\Requests\Factories\Http as HttpRequestFactory;

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

    public function testGet()
    {
        $requestFactory = new HttpRequestFactory;

        $request = $requestFactory->get(self::$application);

        $this->assertTrue($request instanceof HttpRequest);
    }

}
