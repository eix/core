<?php

namespace Nohex\Eix\Core\Settings;

use Nohex\Eix\Core\Settings;

/**
 * Provides application settings for testing purposes.
 */
class MockSettings extends Settings {

    public static $defaultSettings = array(
        'application' => array(
            'id' => 'mock',
            'name' => 'Mock application',
            'rawErrors' => 'on',
        ),
        'locale' => array(
            'default' => 'en',
        ),
        'resources' => array(
            'templates' => array(
                'response' => 'test/resources/response.xml',
            ),
            'pages' => array(
                'location' => 'test/resources/pages/',
            ),
        ),
        'data' => array(
            'sources' => array(
                'http' => 'http =>//eix.nohex.com/data/source',
                'imageStore' => array(
                    'locations' => array(
                        'test' => '/tmp',
                    )
                ),
                'mongodb' => array(
                    'databaseName' => 'test',
                ),
            ),
        ),
    );

    public function __construct($source = null, $environment = null)
    {
        // Use the default test settings in absence of anything else.
        if (empty($source)) {
            $source = self::$defaultSettings;
        }

        parent::__construct($source, $environment);
    }
}
