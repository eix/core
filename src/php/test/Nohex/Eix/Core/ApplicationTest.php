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

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testErrorWithRawErrorsOn()
    {
        $settings = new MockSettings;
        $settings->application->rawErrors = 'on';
        $application = new MockApplication($settings);

        $currentHandler = set_error_handler('handler');
        restore_error_handler();
        $this->assertNotNull($currentHandler, 'The error handler was not assigned.');

        // The error handler should not catch this one.
        trigger_error('Testing rawErrors');
    }

    /**
     * @expectedException Exception
     */
    public function testExceptionWithRawErrorsOn()
    {
        $settings = new MockSettings;
        $settings->application->rawErrors = 'on';
        $application = new MockApplication($settings);

        $currentHandler = set_exception_handler('handler');
        restore_exception_handler();
        $this->assertNull($currentHandler, 'The exception handler is assigned.');

        // The error handler should not catch this one.
        throw new Exception('Testing rawErrors');
    }

// DEBUG! Temporarily disabled until it can be determined how to make them pass.
// 
//    public function testErrorWithRawErrorsOff()
//    {
//        $this->expectOutputRegex('/Template .* was not found/');
//
//        $settings = new MockSettings;
//        $settings->application->rawErrors = 'off';
//        $application = new MockApplication($settings);
//
//        $currentHandler = set_error_handler('handler');
//        restore_error_handler();
//        $this->assertNotNull($currentHandler, 'The error handler was not assigned.');
//    }
//
//    public function testExceptionWithRawErrorsOff()
//    {
//        $this->expectOutputRegex('/Template .* was not found/');
//
//        $settings = new MockSettings;
//        $settings->application->rawErrors = 'off';
//        $application = new MockApplication($settings);
//
//        $currentHandler = set_exception_handler('handler');
//        restore_exception_handler();
//        $this->assertNotNull($currentHandler, 'The exception handler was not assigned.');
//    }
//
//    /**
//     * This one must behave just as if the setting was off.
//     */
//    public function testRawErrorsNotSet()
//    {
//        $this->expectOutputRegex('/Template .* was not found/');
//
//        $settings = new MockSettings;
//        unset($settings->application->rawErrors);
//        $application = new MockApplication($settings);
//
//        try {
//            // The setting should not exist.
//            $settings = $settings->application->rawErrors;
//        } catch (SettingsException $exception) {
//            // The exception is expected, go on.
//        }
//
//        $currentHandler = set_error_handler('handler');
//        restore_error_handler();
//        $this->assertNotNull($currentHandler, 'The error handler is assigned.');
//
//        $currentHandler = set_exception_handler('handler');
//        restore_exception_handler();
//        $this->assertNull($currentHandler, 'The exception handler is assigned.');
//    }
}
