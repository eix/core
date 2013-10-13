<?php

namespace Nohex\Eix\Services\Net\Mail;

/**
 * Representation of an address in an e-mail.
 */
class Address
{
    private $address;
    private $name;

    // RFC2822- & RFC1035-compliant address validation.

    const ADDRESS_REGEXP = '/^[-a-z0-9!#$%&\'*+\/=?^_`{|}~]+(\.[-a-z0-9!#$%&\'*+\/=?^_`{|}~]+)*@(([a-z]([-a-z0-9]*[a-z0-9]+)?){1,63}\.)+([a-z]([-a-z0-9]*[a-z0-9]+)?){2,63}([a-z]([-a-z0-9]*[a-z0-9]+)?)$/i';

    public function __construct($address, $name = null)
    {
        $this->setAddress($address);
        $this->setName($name);
    }

    /*
     * Sets the address, only if it is correct.
     */

    public function setAddress($address)
    {
        if (preg_match(self::ADDRESS_REGEXP, $address)) {
            $this->address = $address;
        } else {
            throw new Exception("Malformed e-mail address: $address");
        }
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function toString()
    {
        return $this->name ? "{$this->name} <{$this->address}>" : $this->address;
    }

}
