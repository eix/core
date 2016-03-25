<?php

namespace Eix\Core;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadDefault()
    {
        // In case the default settings location is available in the test
        // environment.
        if (is_readable(Settings::DEFAULT_SETTINGS_LOCATION)) {
            new Settings;
        } else {
            try {
                new Settings;
            } catch (\Exception $exception) {
                $this->assertTrue($exception instanceof Settings\Exception);
            }
        }
    }

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testLoadUnsupportedSource()
    {
        new Settings(1);
    }

    public function testLoadFromFile()
    {
        $settings = new Settings('data/resources/test/');

        $this->assertEquals($settings->application->id, 'mock');
    }

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testLoadFromInexistentFile()
    {
        // Should complain about a non-available settings file.
        new Settings('data/resources/test/blah');
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

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testLoadFromNonExistentFile()
    {
        new Settings('/path/to/nonexistent/file' . rand(9999, 99999));
    }

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testLoadFromMalformedFile()
    {
        new Settings('data/resources/test/settings/malformed/');
    }

    public function testLoadEnvironmentSettings()
    {
        $settings = new Settings('data/resources/test/settings/environment/');
        $this->assertEquals(Settings::ENV_TEST, $settings->environment);
    }

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testLoadFromEmptyFile()
    {
        new Settings('data/resources/test/empty/');
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

    public function testGetDefault()
    {
        $expectedValue = 'xxx';
        $settings = new Settings(['setting']);

        $this->assertEquals($expectedValue, $settings->get('anotherSetting', $expectedValue, false));
    }

    /**
     * @expectedException Eix\Core\Settings\Exception
     */
    public function testGetAndFail()
    {
        $settings = new Settings(['setting']);
        $settings->anotherSetting;
    }

    public function testMergeSettings()
    {
        $array1 = [
            'key1' => 'value1',
            'matching_key' => 'another_value',
            'more_settings' => [
                'sub_setting_1' => 1,
                'sub_setting_3' => 3,
            ]
        ];

        $array2 = [
            'key2' => 'value2',
            'matching_key' => 'replaced_value',
            'more_settings' => [
                'sub_setting_2' => 2,
                'sub_setting_3' => 'x',
            ]
        ];

        $expectedArray = [
            'key1' => 'value1',
            'key2' => 'value2',
            'matching_key' => 'replaced_value',
            'more_settings' => [
                'sub_setting_1' => 1,
                'sub_setting_2' => 2,
                'sub_setting_3' => 'x',
            ]
        ];

        $mergedArray = Settings::mergeSettings($array1, $array2);

        $this->assertEquals($mergedArray, $expectedArray);
    }

    public function testSetDefaultEnvironment()
    {
        // Remove environment variables.
        putenv(Settings::ENV . '=');
        unset($_SERVER[Settings::ENV]);
        unset($_ENV[Settings::ENV]);

        // Ensure the Eix environment is set to production.
        $this->assertEquals(Settings::ENV_PRODUCTION, Settings::getEnvironment());
    }
}
