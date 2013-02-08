<?php
/**
 * Holds a representation of a user of the system.
 */

namespace Nohex\Eix\Core;

use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Data\Sources\MongoDB as DataSource;
use Nohex\Eix\Services\Net\Http\NotAuthorisedException;
use Nohex\Eix\Services\Net\Http\NotAuthenticatedException;
use Nohex\Eix\Services\Identity\Provider as IdentityProvider;

class User extends \Nohex\Eix\Services\Data\Entity
{
    const COLLECTION = 'users';

    protected $name;
    protected $email;
    protected $isAuthenticated = false;

    /**
     * The permissions define which classes and functions this user is allowed
     * to execute. They are expressed as an array of classes which contain an
     * array of functions.
     *
     * This array means the user is authorised to everything:
     * array('*');
     *
     * This array means the user is authorised to all within the Xxx class:
     * array(
     *   'Xxx' => '*',
     * );
     *
     * This array means the user is authorised to the read and write functions
     * within the Xxx class:
     * array(
     *   'Xxx' => array(
     *     'read',
     *     'write',
     *   ),
     * );
     *
     * @var array
     */
    protected $permissions = array();

    public function __construct($data, $isNew = true)
    {
        // Make sure all users have their own ID.
        if (!@$data['id']) {
            $data['id'] = static::getNewUserId();
        }

        parent::__construct($data, $isNew);

        if (!$this->firstSeen) {
            $this->firstSeen = new \DateTime;
        }

        $this->lastSeen = new \DateTime;
    }

    protected function assignDataSource()
    {
        $this->dataSource = DataSource::getInstance(static::COLLECTION);
    }

    protected function getFactory()
    {
        return Users::getInstance();
    }

    protected function getFields()
    {
        return array(
            'id',
            'name',
            'email',
            'permissions',
        );
    }

    protected function getFieldValidators()
    {
        return array(
            'id' => array('NonEmpty'),
            'name' => array('NonEmpty'),
        );
    }

    /**
     * Generates an ID for a new user.
     *
     * @return string
     */
    private static function getNewUserId()
    {
        // Get an Eix-compliant user ID from a unique ID.
        return Users::getUserId(array(uniqid()));
    }

    /**
     * Checks whether a user is allowed to execute a particular function in a
     * class.
     *
     * @param  mixed                  $object       the object or class name that contains the function.
     * @param  type                   $functionName the function name to execute.
     * @throws NotAuthorisedException
     */
    public function checkAuthorisation($object, $functionName)
    {
        // Check whether the user has authenticated.
        if ($this->isAuthenticated()) {
            // The user is authenticated. Proceed.
            $class = $object;
            if (is_object($class)) {
                $class = get_class($class);
            }

         Logger::get()->debug("Checking authorisation for {$class}:{$functionName}...");

            // Check that at least one condition is valid.
            if (!(
                // User has total authorisation.
                in_array('*', $this->permissions)
                // User has total class authorisation for this class.
                || @$this->permissions[$class]['*']
                // User has authorisation for this function in this class.
                || @$this->permissions[$class][$functionName]
            )) {
                // User has no authorisation.
                throw new NotAuthorisedException("The current user does not have permission for '{$class}.{$functionName}'.");
            }

         Logger::get()->debug('Authorised.');
        } else {
            // The user is not authenticated.
            throw new NotAuthenticatedException('There is no current authenticated user.');
        }
    }

    /**
     * Flag the user as no longer in existence, so that it does not
     * automatically get saved in the session.
     */
    public function destroy()
    {
        parent::destroy();

     Logger::get()->debug('Destroying user ' . $this->id);
        // If this user is the one stored in the session, destroy that too.
        if (!empty($_SESSION) && ($_SESSION['current_user'] == $this)) {
            unset($_SESSION['current_user']);
        }
    }

    /**
     * Authenticates a user against an identity provider.
     *
     * @param IdentityProvider $identityProvider
     */
    public function authenticate(IdentityProvider $identityProvider)
    {
        try {
            // Authenticate the user.
            $identityProvider->authenticate();
            // Success!
            $this->isAuthenticated = true;
            // Set the data obtained from the identity provider.
            $this->name = $identityProvider->getUserName();
            $this->email = $identityProvider->getUserEmail();
        } catch (IdentityException $exception) {
            $this->isAuthenticated = false;
            throw $exception;
        }
    }

    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Returns the properties of a user that can be published.
     */
    public function getPublicData()
    {
        return array(
            'name' => $this->name,
            'email' => $this->email
        );
    }
}
