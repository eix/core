<?php

namespace Eix\Core;

use Eix\Core\Requests\Http as HttpRequest;

/**
 * Fake application for use in unit tests.
 */
class MockApplication extends Application
{
    public function __construct(Settings $settings = null)
    {
        // Customise settings.
        if (empty($settings)) {
            $settings = new MockSettings;
        }

        // Inject a mock set of default routes.
        HttpRequest::setRoutes(array(array(
            'uri' => '/request/uri',
            'responder' => '\\Nohex\\Eix\\Core\\Responders\\Http\\Page',
            'section' => 'home',
            'page' => 'index',
        )));

        parent::__construct($settings);

        // Set up the rest of the environment.
        $_SERVER['REQUEST_URI'] = '/request/uri';
    }
}
