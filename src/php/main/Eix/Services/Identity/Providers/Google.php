<?php

namespace Eix\Services\Identity\Providers;

use Eix\Services\Identity\Providers\OpenId;

/**
 * Provides identity services using Google by means of OpenID.
 */
class Google extends OpenId
{
    const GOOGLE_OPENID_ENDPOINT = 'https://www.google.com/accounts/o8/id';

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->getConsumer()->identity = self::GOOGLE_OPENID_ENDPOINT;
    }
}
