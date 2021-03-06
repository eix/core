<?php
/**
 * Unit test for class Eix\Core\Responses\Http\Media\XslPage.
 */

namespace Eix\Core\Responses\Http\Media;

use Eix\Core\MockApplication;
use Eix\Core\Responses\Http\Media\XslPage;

class XslPageTest extends \PHPUnit_Framework_TestCase
{
    const TEMPLATE_ID = 'test_template';

    private static $application;
    private static $data = array();

    protected function setUp()
    {
        self::$data = array(
            'key' => 'value',
        );

        self::$application = new MockApplication;
    }

    public function testDefaultConstructor()
    {
        $object = new XslPage(self::TEMPLATE_ID, self::$data);

        $this->assertTrue($object instanceof XslPage);
    }

    protected function tearDown()
    {
        self::$data = null;
        self::$application = null;
    }

}
