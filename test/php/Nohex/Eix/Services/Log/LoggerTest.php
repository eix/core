<?php
/**
 * Unit test for class Nohex\Eix\Services\Log\Logger.
 */

namespace Nohex\Eix\Services\Log;

use Nohex\Eix\Services\Log\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'logger_id';

    public function testDefaultConstructor()
    {
        $logger = new Logger(self::ID);

        $this->assertTrue($logger instanceof Logger);
    }

    public function testStaticInstance()
    {
        $logger1 = Logger::get();
        $logger2 = Logger::get();

        $this->assertSame($logger1, $logger2);
    }

}
