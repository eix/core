<?php

/*
 * Page represents a web page in View instance.
 * max@nohex.com 20050817
 */

namespace Nohex\Eix\Core\Responses\Http\Media;

use Nohex\Eix\Services\Log\Logger;

abstract class WebPage extends Medium
{
    protected $template;

    /*
     * Prepares the document from the template.
     */
    protected function prepare()
    {
        // Set the web page content into $document.
        $template = $this->getTemplate();
        if ($template) {
            $this->document = $template;
        } else {
            throw new Exception('There is no template', 500);
        }
    }

    /*
     * Outputs the page.
     */
    public function render()
    {
        $this->prepare();

        if ($this->document) {
            // Output the page.
            echo $this->document;

         Logger::get()->debug(get_class($this) . ': medium has finished rendering.');
        } else {
            throw new Exception('There is no document.');
        }
    }
}
