<?php

namespace Nohex\Eix\Services\Identity\Providers;

use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Identity\Provider;
use Nohex\Eix\Library\LightOpenId as OpenIdConsumer;
use Nohex\Eix\Services\Net\Http\NotAuthenticatedException;

/**
 * Provides identity services using OpenID.
 */
class OpenId implements Provider
{
    protected static $consumer;
    protected $returnUrl;
    protected $authenticationUrl;
    protected $requiredFields = array();
    protected $optionalFields = array();
    protected $isAuthenticated = false;

    public function __construct($host)
    {
        $host = $host ?: @$_SERVER['SERVER_NAME'];

        static::getConsumer()->setHost($host);

        // Set the user data to be returned upon validation.
        static::getConsumer()->required = array(
            'namePerson',
            'contact/email',
            'namePerson/first',
            'namePerson/last',
        );
        static::getConsumer()->optional = array(
            'namePerson/friendly',
        );
    }

    public function getAuthenticationUrl()
    {
        return static::getConsumer()->authUrl();
    }

    /**
     * Makes sure the user is authenticated. If not, an exception is thrown.
     * @throws NotAuthenticatedException
     */
    public function authenticate()
    {
     Logger::get()->debug('Authenticating OpenID user...');
        if (!$this->isAuthenticated) {
            if (!static::getConsumer()->validate()) {
             Logger::get()->debug('  Failed!');
                throw new NotAuthenticatedException('OpenID authentication failed.');
            } else {
                $this->isAuthenticated = true;
            }
        }

     Logger::get()->debug('  OK!');
    }

    /**
     * Provides a unique user ID that can be stored for reference.
     */
    public function getUserId()
    {
        return static::getConsumer()->data['openid_identity'];
    }

    public function getUserName()
    {
        return sprintf('%s %s',
            static::getConsumer()->data['openid_ext1_value_namePerson_first'],
            static::getConsumer()->data['openid_ext1_value_namePerson_last']
        );
    }

    public function getUserEmail()
    {
        return static::getConsumer()->data['openid_ext1_value_contact_email'];
    }

    public static function setConsumer($consumer)
    {
        static::$consumer = $consumer;
    }

    public function getConsumer($host = null)
    {
        if (empty(static::$consumer)) {
            static::setConsumer(new OpenIdConsumer($host));
        }

        return static::$consumer;
    }
}
