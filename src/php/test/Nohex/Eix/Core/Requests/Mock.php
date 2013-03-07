<?php
/**
 * Mock request.
 */

namespace Nohex\Eix\Core\Requests;

use Nohex\Eix\Core\Request;

class Mock implements Request
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
