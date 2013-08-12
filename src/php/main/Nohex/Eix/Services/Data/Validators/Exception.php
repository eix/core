<?php

namespace Nohex\Eix\Services\Data\Validators;

class Exception extends \Nohex\Eix\Core\Exception
{
    // The names of the fields that did not pass their validations, along with
    // the validations they did not pass.
    private $validationStatus = array();

    public function setValidationStatus($validationStatus)
    {
        $this->validationStatus = $validationStatus;
    }

    public function getValidationStatus()
    {
        return $this->validationStatus;
    }

}
