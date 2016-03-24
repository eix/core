<?php
/**
 * Unit test for class Eix\Core\Requests\Http\Error.
 */

namespace Eix\Core\Requests\Http;

use Eix\Core\Requests\Http\Error as ErrorRequest;
use Eix\Core\Requests\Exception;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    private $throwable;

    protected function setUp()
    {
        $this->throwable = new Exception('Test exception');
    }

    protected function tearDown()
    {
        unset($this->throwable);
    }

    public function testDefaultConstructor()
    {
        $request = new ErrorRequest;

        $request->setThrowable($this->throwable);

        $this->assertEquals(
            $request->getThrowable(),
            $this->throwable
        );
    }

}
