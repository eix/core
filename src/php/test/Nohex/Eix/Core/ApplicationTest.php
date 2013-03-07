<?php

namespace Nohex\Eix\Core;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Nohex\Eix\Core\Exception
     */
    public function testEmptyStaticInstance()
    {
        $application = Application::getCurrent();
    }

    public function testSingleton()
    {
        $applicationA = new MockApplication;
        $applicationB = Application::getCurrent();

        $this->assertSame($applicationA, $applicationB);
    }
}
