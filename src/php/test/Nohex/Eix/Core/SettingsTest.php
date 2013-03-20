<?php

namespace Nohex\Eix\Core;

use Nohex\Eix\Core\Settings;
use Nohex\Eix\Core\MockSettings;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromFile()
    {
        $settings = new Settings('data/resources/test/');

        $this->assertEquals($settings->application->id, 'mock');
    }

    /**
     * @expectedException Nohex\Eix\Core\Settings\Exception
     */
    public function testLoadFromInexistentFile()
    {
        // Should complain about a non-available settings file.
        $settings = new Settings('data/resources/test/blah');
    }

    public function testLoadFromArray()
    {
        $settings = new Settings(array(
            'application' => array(
                'id' => 'mock',
            ),
        ));

        $this->assertEquals($settings->application->id, 'mock');
    }

    public function testLoadFromObject()
    {
        $settingsObject = new \stdClass;
        $settingsObject->application = new \stdClass;
        $settingsObject->application->id = 'mock';

        $settings = new Settings($settingsObject);

        $this->assertEquals($settings->application->id, 'mock');
    }


    public function testSet()
    {
        $settings = new Settings(array(
            'application' => array(
                'id' => 'mock',
            ),
        ));
        $settings->application->id = 'mick';

        $this->assertEquals($settings->application->id, 'mick');
    }
}
