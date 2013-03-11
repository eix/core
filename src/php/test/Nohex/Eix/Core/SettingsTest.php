<?php

namespace Nohex\Eix\Core;

use Nohex\Eix\Core\Settings;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        // Should complain about a non-available settings file.
        $settings = new Settings('data/resources/test/');

        $this->assertEquals($settings->application->id, 'mock');
    }

}
