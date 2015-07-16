<?php

namespace Eix\Services\Data\Sources\Connectors;

class MockMongoCollection
{
    public $returns = array();

    public function find($filter, $fields)
    {
        return $this->returns[self::getSignature($filter, $fields)];
    }

    /**
     * Returns a hash consisting of the concatenated serialised strings of the
     * the arguments. No arguments are declared to leave the function open to
     * accept as many as they come.
     */
    private static function getSignature(array $arguments)
    {
        $signatureString = '';
        foreach (func_get_args() as $argument) {
            $signatureString += serialize($argument);
        }

        return sha1($signatureString);
    }
}
