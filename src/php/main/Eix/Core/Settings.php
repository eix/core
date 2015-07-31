<?php

namespace Eix\Core;

use Eix\Services\Log\Logger;

class Settings
{
    // The default settings script is in the 'environment' folder of the
    // application.
    const DEFAULT_SETTINGS_LOCATION = '../data/environment/';

    private $settings;
    private static $environment;

    /**
     * Builds a settings object.
     *
     * @param mixed $source a location where settings can be loaded, or an
     * object or array with settings.
     * @throws Settings\Exception
     */
    public function __construct($source = null)
    {
        // Parse the settings source.
        if (empty($source)) {
            // No source specified, load from the default location.
            $this->loadFromLocation(self::DEFAULT_SETTINGS_LOCATION);
        } elseif (is_array($source)) {
            // Source is an array, convert to object.
            $this->settings = self::objectify($source);
        } elseif (is_object($source)) {
            // Source is an object, assign directly.
            $this->settings = $source;
        } elseif (is_string($source)) {
            // Source is a string, assume it's a settings location.
            $this->loadFromLocation($source);
        } else {
            // Source is anything else, don't know what to do with it.
            throw new Settings\Exception('Settings source is unknown.');
        }
    }

    /**
     * Load settings from a file.
     *
     * @param string $location The location of the settings file.
     * @throws Settings\Exception
     */
    private function loadFromLocation($location)
    {
        Logger::get()->debug('Loading settings...');
        // Add the trailing slash to the folder if it is missing.
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
                // Load settings customised for the current environment.
                $environmentSettings = null;
                $environment = self::getEnvironment();
                if ($environment) {
                    $environmentSettingsLocation = sprintf(
                        '%ssettings-%s.json',
                        $location,
                        strtolower($environment)
                    );
                    if (is_readable($environmentSettingsLocation)) {
                        $environmentSettings = json_decode(file_get_contents($environmentSettingsLocation), true);
                        if (!empty($environmentSettings)) {
                            $settings = self::mergeSettings($settings, $environmentSettings);
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

    public function get($key, $default = null, $failOnMissing = false)
    {
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

    /**
     * Allow settings to be set directly using their keys as members.
     * @param string $key the setting key
     * @param mixed $value the setting value
     */
    public function __set($key, $value)
    {
        $this->settings->$key = $value;
    }

    /**
     * Convenience function that allows querying the settings by
     * using their ID as a member.
     *
     * Please note that retrieving an unknown value in this fashion
     * will result in an exception.
     *
     * @param string $settingId the setting key
     * @return mixed the setting value
     * @throws Settings\Exception
     */
    public function __get($settingId)
    {
        return $this->get($settingId, null, true);
    }

    /**
     * Converts an array to an object recursively.
     * @param array $array the array to convert
     * @return object the converted array
     */
    private static function objectify(array $array)
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = self::objectify($item);
            }
        }

        return (object)$array;
    }

    /**
     * Set the operating environment.
     *
     * @param string $environment the new environment.
     */
    public static function setEnvironment($environment)
    {
        self::$environment = $environment;
    }

    /**
     * Find out the current environment.
     *
     * @return string the current environment.
     */
    public static function getEnvironment()
    {
        if (empty(self::$environment)) {
            self::$environment = getenv('EIX_ENV')
                ?: @$_SERVER['EIX_ENV']
                    ?: @$_ENV['EIX_ENV'];

            // If the environment cannot be inferred, assume production.
            if (empty(self::$environment)) {
                self::$environment = 'pro';
            }
        }

        return self::$environment;
    }

    /**
     * Merges two arrays, just like array_merge_recursive, but the second array
     * overwrites the first one's values if there is a match.
     *
     * @param array $generalSettings the base array
     * @param array $environmentSettings the overwriting array
     * @return array the merged settings.
     */
    public static function mergeSettings($generalSettings, $environmentSettings)
    {
        $mergedSettings = $generalSettings;

        foreach ($environmentSettings as $key => &$value) {
            if (
                is_array($value)
                && isset($mergedSettings[$key])
                && is_array($mergedSettings [$key])
            ) {
                $mergedSettings[$key] = self::mergeSettings($mergedSettings[$key], $value);
            } else {
                $mergedSettings [$key] = $value;
            }
        }

        return $mergedSettings;
    }
}
