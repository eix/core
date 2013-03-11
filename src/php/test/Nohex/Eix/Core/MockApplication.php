<?php

namespace Nohex\Eix\Core;

/**
 * Fake application for use in unit tests.
 */
class MockApplication extends Application
{
    public function __construct(Settings $settings = null)
    {
        // Customise settings.
        $settings = new Settings('data/resources/test/');

        parent::__construct($settings);
    }
}
