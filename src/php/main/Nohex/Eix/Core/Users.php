<?php
/**
 * Factory-like class to deal with groups of User objects.
 */

namespace Nohex\Eix\Core;

use Nohex\Eix\Core\Application;
use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;
use Nohex\Eix\Services\Net\Http\NotAuthorisedException;
use Nohex\Eix\Services\Net\Http\NotFoundException;
use Nohex\Eix\Services\Identity\Provider;
use Nohex\Eix\Core\User;

class Users extends \Nohex\Eix\Services\Data\Factory
{
    const COLLECTION = 'users';
    const ENTITIES_CLASS_NAME = '\Nohex\Eix\Core\User';
    const USER_HASH_LENGTH = 8; // Length of CRC32b
    const SESSION_DURATION = 600; // 600 seconds = 10 minutes

    protected static $current;
    protected static $cookieName;

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }

    /**
     * Returns the current user.
     *
     * @return User the returned object is User or a descendant thereof.
     */
    public static function getCurrent()
    {
        if (empty(self::$current)) {
            Logger::get()->debug('Checking session for users...');
            self::$current = static::getInstance()->getFromSession();

            // Nothing there, new visitor then.
            if (empty(self::$current)) {
                throw new \RuntimeException('Unable to obtain a user');
            }

            // Update last seen time.
            self::$current->lastSeen = new \DateTime;

            Logger::get()->debug(
                'Current user is ' . self::$current->getId()
            );
        }

        return self::$current;
    }

    public static function setCurrent(User $user)
    {
        self::$current = $user;

        // Store it in the session.
        $_SESSION['current_user'] = &self::$current;
        $_SESSION['timestamp'] = time();
    }

    /**
     * Returns uniform IDs based on various user data. The ID is a hash of the
     * data joined by the salt.
     *
     * @param  array  $userData as many fields of user data as needed.
     * @return string
     */
    public static function getUserId(array $userData)
    {
        $salt = Application::getCurrent()->getSalt();

        return hash('crc32b', join($salt, $userData));
    }

    /**
     * Obtains a user reference from a cookie.
     */
    private static function getFromSession()
    {
        if (empty($_SESSION['current_user'])) {
            // No user in session, create one.
            $entityClassName = static::ENTITIES_CLASS_NAME;
            $user = new $entityClassName(array(
                'source' => 'cookie',
            ));
            self::setCurrent($user);
        } else {
            Logger::get()->debug('Found user in session.');
            // Regenerate session ID every SESSION_DURATION minutes.
            if (time() > ($_SESSION['timestamp'] +  + self::SESSION_DURATION)) {
                Logger::get()->debug('Regenerated session ID.');
                session_regenerate_id();
            }
        }

        return $_SESSION['current_user'];
    }

    /**
     * Use the specified identity provider to establish an authenticated
     * user.
     */
    public static function getFromIdentityProvider(Provider $identityProvider)
    {
        // Have the identity provider authenticate the current identity.
        $identityProvider->authenticate();
        // The identity is valid according to the provider, so authentication is
        // passed.
        $userId = $identityProvider->getUserId();
        // Check for authorisation.
        try {
            Logger::get()->debug(sprintf(
                'Checking OpenID user %s...',
                $userId
            ));
            $user = Users::getInstance()->findEntity($userId);
            // The user is valid according to our records. Keep it in the session.
            $user->authenticate($identityProvider);
        } catch (NotFoundException $exception) {
            Logger::get()->warning(sprintf(
                'OpenID user %s is not known.',
                $userId
            ));
            throw new NotAuthorisedException(
                'OpenID user is not authorised.'
            );
        }

        return $user;
    }
}
