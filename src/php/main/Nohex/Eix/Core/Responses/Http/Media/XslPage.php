<?php

/*
 * Page represents a web page in View instance.
 * max@nohex.com 20050817
 */

namespace Nohex\Eix\Core\Responses\Http\Media;

use Nohex\Eix\Core\Application;
use Nohex\Eix\Services\Log\Logger;

class XslPage extends WebPage
{
    const DATA_PATH_ROOT = '/response';

    // DOM document that will hold an XML representation of the data.
    protected $dataDocument;

    private static $pagesLocation;
    private static $locale;

    public function __construct($templateId, $data)
    {
        // An XML document is created to hold data.
        $this->dataDocument = new Library\Dom(
            Application::getCurrent()->getSettings()->resources->templates->response
        );

        $this->templateId = $templateId;
        $this->data = $data;

        if (empty(self::$pagesLocation)) {
            self::$pagesLocation = Application::getSettings()->resources->pages->location;
        }
        if (empty(self::$locale)) {
            self::$locale = Application::getCurrent()->getLocale();
        }
    }

    private function getTemplateLocation()
    {
        return sprintf('%s/%s/%s.xsl',
            self::$pagesLocation,
            self::$locale,
            $this->templateId
        );
    }

    /*
     * Returns the data DOM document, or a particular
     * node's content, if specified in $path.
     */
    public function getData($path = null)
    {
        return is_null($path) ? $this->dataDocument : $this->dataDocument->getNodeContent($path);
    }

    protected function getTemplate()
    {
        $location = $this->getTemplateLocation();
        if (file_exists($location)) {
            $template = new Library\Dom($location);
            if (!$template) {
                throw new Exception("Template '{$location}' failed to load.", 404);
            }
        } else {
            throw new Exception("Template '{$location}' was not found.", 404);
        }

        return $template;
    }

    public function setTitle($title)
    {
        parent::setTitle($title);
        $this->addData("view-title", $title, true);
    }

    /**
     * Prepares the document by merging
     * the template and the data.
     */
    protected function prepare()
    {
        // Set data into dataDocument structure;
        if (is_array($this->data)) {
            foreach ($this->data as $key => $value) {
                $this->addData($key, $value);
            }
        }

     Logger::get()->debug("Response data:\n" . $this->dataDocument->toString(false));

        // Merge data with template.
        $this->document = $this->dataDocument->transform($this->getTemplate());
    }

    /*
     * Adds a value to the XML data document.
     */
    protected function addData($name, $value)
    {
        if (is_array($value)) {
            $this->dataDocument->addArray(self::DATA_PATH_ROOT, $value, $name);
        } elseif ($value instanceof DOMNodeList) {
            $this->dataDocument->copyNodes(self::DATA_PATH_ROOT, $value);
        } elseif ($value instanceof Library\Dom) {
            $this->dataDocument->copyNodes(self::DATA_PATH_ROOT, $value->getNodes('/'));
        } elseif (is_object($value)) {
            $this->dataDocument->addObject(self::DATA_PATH_ROOT, $value, $name);
        } else {
            $this->dataDocument->addNode(self::DATA_PATH_ROOT, $name, $value);
        }
    }

    /*
     * Adds an XML chunk to the data document.
     */
    public function addXMLData($path, $value)
    {
        $this->dataDocument->copyNodes($path, $value);
    }

}
