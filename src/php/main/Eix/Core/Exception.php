<?php
/**
 * Exception thrown by the Core classes.
 */

namespace Eix\Core;

class Exception extends \Exception
{
    protected $message = 'Core malfunction.';
    protected $code = 500;

    public function __construct($message = null, $code = 0, $previous = null)
    {
        // Support old format exceptions.
        if (is_array($code)) {
            $code =  $code['code'];
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the message for the exception.
     */
    public function __toString()
    {
        return sprintf('[%s:%s] %s in %s:%d',
            get_class($this),
            $this->code,
            $this->message,
            $this->getFile(),
            $this->getLine()
        );
    }

}
