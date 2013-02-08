<?php
/**
 * Mock consumer for identity providers.
 */

namespace Nohex\Eix\Services\Identity\Providers\Consumers;

class Mock
{
    public $required;
    public $optional;
    private $host;

    public function setHost($host)
    {
        $this->host = $host;
    }
}
