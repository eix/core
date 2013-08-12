<?php

namespace Nohex\Eix\Core\Responses\Http;

use Nohex\Eix\Core\Requests\Http as HttpRequest;

/**
 * Response which outputs an XML representation of the data.
 */
class Xml extends \Nohex\Eix\Core\Responses\Http
{
    const CONTENT_TYPE = 'application/xml';

    public function __construct(HttpRequest $request = null)
    {
        $this->setContentType(self::CONTENT_TYPE);

        parent::__construct($request);
    }

    public function issue()
    {
        $continue = parent::issue();
        if ($continue) {
            $xmlOutput = new \SimpleXMLElement('<response source="eix"/>');
            array_walk_recursive($this->getData(), function($value, $key) use (&$xmlOutput) {
                $xmlOutput->addChild($key, $value);
            });

            echo $xmlOutput->asXML();
        }

        return $continue;
    }

}
