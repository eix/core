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

    public function testRawErrorsOn() {
        $settings = new MockSettings;
        $settings->application->rawErrors = 'off';
        $application = new Application($settings);
    }

    public function testRawErrorsOff() {
        $settings = new MockSettings;
        $settings->application->rawErrors = 'off';
        $application = new Application($settings);
    }

    public function testNoRawErrors() {
        $settings = new MockSettings;
        unset($settings->application->rawErrors);
        $application = new Application($settings);
    }
}
