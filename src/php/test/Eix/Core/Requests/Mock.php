<?php
/**
 * Mock request.
 */

namespace Eix\Core\Requests;

use Eix\Core\Request;

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
