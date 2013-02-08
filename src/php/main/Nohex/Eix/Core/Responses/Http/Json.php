<?php

namespace Nohex\Eix\Core\Responses\Http;

use Nohex\Eix\Core\Requests\Http as HttpRequest;

/**
 * Response which outputs a JSON representation of the data.
 */
class Json extends \Nohex\Eix\Core\Responses\Http
{
    const CONTENT_TYPE = 'application/json';

    public function __construct(HttpRequest $request = null)
    {
        $this->setContentType(self::CONTENT_TYPE);

        parent::__construct($request);
    }

    public function issue()
    {
        parent::issue();

        $data = $this->getData();
        // If there is only one element, don't output the key, just the value.
        if (count($data) == 1) {
            $data = current($data);
        }

        // Output the JSON.
        echo json_encode($data);
    }
}
