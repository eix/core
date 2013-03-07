<?php
/**
 * Unit test for class Nohex\Eix\Core\Exception.
 */

namespace Nohex\Eix\Core;

use Nohex\Eix\Core\Exception;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'message';
    const INNER_MESSAGE = 'inner message';
    const CODE = 12345;

    public function testDefaultExceptionMessage()
    {
        $exception = new Exception;

        $this->assertEquals($exception->getMessage(), '');
    }

    public function testDefaultExceptionCode()
    {
        $exception = new Exception;

        $this->assertEquals($exception->getCode(), 500);
    }

    public function testConstructorWithData()
    {
        $innerException = new \RuntimeException(self::INNER_MESSAGE);
        $exception = new Exception(self::MESSAGE, self::CODE, $innerException);

        $this->assertEquals($exception->getMessage(), self::MESSAGE);
        $this->assertEquals($exception->getCode(), self::CODE);
        $this->assertTrue($exception->getPrevious() instanceof \RuntimeException);
        $this->assertSame($exception->getPrevious(), $innerException);
        $this->assertEquals($exception->getPrevious()->getMessage(), self::INNER_MESSAGE);
    }
}
