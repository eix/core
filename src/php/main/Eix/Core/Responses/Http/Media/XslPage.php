<?php

namespace Eix\Core\Responses\Http\Media;

use Eix\Core\Application;
use Eix\Services\Log\Logger;
use Eix\Core\Responses\Http\Media\Exception as MediaException;

/*
 * A page that is build using XML data and an XSL stylesheet.
 */
class XslPage extends WebPage
{
    const DATA_PATH_ROOT = '/response';

    // DOM document that will hold an XML representation of the data.
    protected $dataDocument;

    private static $pagesLocation;
    private static $locale;

    /**
     *
     * @param  type           $templateId
     * @param  type           $data
     * @throws MediaException
     */
    public function __construct($templateId, $data)
    {
        if (empty($templateId)) {
            throw new MediaException('No template has been specified.');
        }

        $this->templateId = $templateId;
        $this->data = $data;

        // An XML document is created to hold data.
        $this->dataDocument = new Library\Dom(
            Application::getCurrent()->getSettings()->resources->templates->response
        );

        if (empty(self::$pagesLocation)) {
            self::$pagesLocation = Application::getSettings()->resources->pages->location;
        }
        if (empty(self::$locale)) {
            self::$locale = Application::getCurrent()->getLocale();
        }
    }

    private function getTemplateLocation()
    {
        // Try with full locale.
        $location = sprintf('%s/%s/%s.xsl',
            self::$pagesLocation,
            self::$locale,
            $this->templateId
        );

        // If the template is not readable...
        if (!is_readable($location)) {
            // ... and the locale carries a country code, remove it and try again.
            $underscorePosition = strpos(self::$locale, '_');
            if ($underscorePosition > 0) {
                $locale = substr(self::$locale, 0, $underscorePosition);
                // Try with partial locale.
                $location = sprintf('%s/%s/%s.xsl',
                    self::$pagesLocation,
                    $locale,
                    $this->templateId
                );
            }
        }

        return $location;
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
                throw new MediaException("Template '{$location}' failed to load.", 404);
            }
        } else {
            throw new MediaException("Template '{$location}' was not found.", 404);
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
        if (!empty($name)) {
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
    }

    /*
     * Adds an XML chunk to the data document.
     */
    public function addXMLData($path, $value)
    {
        $this->dataDocument->copyNodes($path, $value);
    }

}
