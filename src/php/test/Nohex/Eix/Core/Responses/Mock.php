<?php

/**
 * Provides a fixed response for testing purposes.
 */

namespace Nohex\Eix\Core\Responses;

use Nohex\Eix\Core\Responses\Data as DataResponse;

class Mock extends DataResponse
{
    public function issue()
    {
        return $this->data;
    }
}
