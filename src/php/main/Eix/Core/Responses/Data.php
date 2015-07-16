<?php

/**
 * Defines a response which carries data.
 */

namespace Eix\Core\Responses;

use Eix\Core\Response;

abstract class Data implements Response
{
     private $data;

   /**
     * Stores the response data in a key-value fashion.
     *
     * @param string $key   the key under which the data is stored.
     * @param mixed  $value the data.
     */
    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Stores the response data in a key-value fashion, adding the data to the
     * existing set.
     *
     * @param string $key   the key under which the data is stored.
     * @param mixed  $value the data.
     */
    public function addData($key, $value)
    {
        if (isset($this->data[$key])) {
            $this->data[$key] = array_merge_recursive(
                $this->data[$key],
                $value
            );
        } else {
            $this->setData($key, $value);
        }
    }

    public function getData($key = NULL)
    {
        if (empty($key)) {
            return $this->data;
        } else {
            return $this->data[$key];
        }
    }
}
