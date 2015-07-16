<?php

namespace Eix\Services\Net\Mail;

/**
 * Representation of the address of an e-mail recipient.
 */
class Recipient extends Address
{
    private $type;
    private static $ALLOWED_TYPES = array('to', 'cc', 'bcc');

    public function __construct($type, $address, $name = null)
    {
        $this->setType($type);
        parent::__construct($address, $name);
    }

    public function setType($type)
    {
        if (in_array($type, self::$ALLOWED_TYPES)) {
            $this->type = $type;
        } else {
            throw new Exception('Unrecognised recipient type.');
        }
    }

    public function getType()
    {
        return $this->type;
    }

}
