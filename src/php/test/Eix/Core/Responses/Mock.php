<?php

/**
 * Provides a fixed response for testing purposes.
 */

namespace Eix\Core\Responses;

use Eix\Core\Responses\Data as DataResponse;

class Mock extends DataResponse
{
    public function issue()
    {
        return $this->data;
    }
}
