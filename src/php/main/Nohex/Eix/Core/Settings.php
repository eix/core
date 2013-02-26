<?php

namespace Nohex\Eix\Core;

use Nohex\Eix\Services\Log\Logger;

class Settings
{
    private $settings;

    public function __construct($location = null, $environment = null)
    {
        Logger::get()->debug('Loading application settings...');
        if (empty($location)) {
            // The default settings script is in the 'environment' folder of the
            // application.
            $location = '../data/environment/';
        }

        if (substr($location, -1) != DIRECTORY_SEPARATOR) {
            $location .= DIRECTORY_SEPARATOR;
        }
        $settingsLocation = $location . 'settings.json';

        if (is_readable($settingsLocation)) {
            // Load the settings.
            $settings = json_decode(file_get_contents($settingsLocation), true);
            if (empty($settings)) {
                throw new Settings\Exception('Settings file cannot be parsed.');
            } else {
                // Find out the current environment.
                if (empty($environment)) {
                    if (PHP_SAPI == 'cli') {
                        $this->environment = 'cli';
                    } else {
                        $this->environment = getenv('EIX_ENV');
                    }
                }

                // Load settings customised for the current environment.
                $environmentSettings = null;
                if ($this->environment) {
                    $environmentSettingsLocation = sprintf(
                        '%ssettings-%s.json',
                        $location,
                        strtolower($this->environment)
                    );
                    if (is_readable($environmentSettingsLocation)) {
                        $environmentSettings = json_decode(file_get_contents($environmentSettingsLocation), true);
                        if (!empty($environmentSettings)) {
                            $settings = array_merge_recursive($settings, $environmentSettings);
                        }
                    }
                }

                $this->settings = self::objectify($settings);
            }
        } else {
            Logger::get()->error(
                'Application settings not found in %s.',
                $settingsLocation
            );
            throw new Settings\Exception('No settings have been found.');
        }
    }

    public function	get($key, $default = null, $failOnMissing = false) {
        if (isset($this->settings->$key)) {
            return $this->settings->$key;
        } else {
            if ($failOnMissing) {
                throw new Settings\Exception("Could not find a setting identified by '$key'.");
            } else {
                return $default;
            }
        }
    }

    protected function set($id, $value)
    {
        $this->settings[$id] = $value;
    }

    /**
     * Convenience function that allows querying the settings by
     * using their ID as a member.
     * Please note that retrieving an unknown value in this fashion
     * will result in an exception.
     */
    public function __get($settingId)
    {
        return $this->get($settingId, null, true);
    }

    /**
     * Converts an array to an object recursively.
     */
    private static function objectify(array $array)
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = self::objectify($item);
            }
        }

        return (object) $array;
    }
}
