<?php

namespace Nohex\Eix\Core\Requests\Http;

use Nohex\Eix\Core\Requests\Http as HttpRequest;

/**
 * Fixed request for testing purposes.
 */
class Mock extends HttpRequest
{
    public function getResponderClassName()
    {
        return 'MockResponder';
    }

    public function getParameters()
    {
        return array(
            'key1' => 'value1',
            'key2' => 'value2',
        );
    }
}
