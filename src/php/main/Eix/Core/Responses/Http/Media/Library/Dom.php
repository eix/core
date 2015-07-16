<?php

/**
 * Wrapper around PHP's DomDocument to provide advanced node manipulation and
 * XSL transformations.
 */

namespace Eix\Core\Responses\Http\Media\Library;

use Eix\Services\Log\Logger;

class Dom
{
    private $logger;
    // Holds the DOM document.
    private $domDocument = null;

    /**
     * Creates a new Dom document.
     *
     * @param  type          $source   A file location or an XML string.
     * @param  type          $encoding the encoding of the resulting document.
     * @throws Dom\Exception
     */
    public function __construct($source = null, $encoding = "UTF-8")
    {
        // Creation of the DOM document.
        try {
            $this->logger = Logger::get();

            $this->domDocument = new \DOMDocument("1.0", $encoding);

            // If there is any source, get it into the document.
            if ($source) {
                if (is_array($source)) {
                    throw new Dom\Exception("dom-cannot-use-array");
                    // It is not clear where arrayToXML is defined. Maybe it can be removed.
                    // $this->document = $this->arrayToXML($source, $rootElement);
                } elseif (is_string($source)) {
                    // If $source is a string, it can be a file or
                    // an XML representation of a document.

                    // If the source starts with a < character,
                    // we can be pretty sure we are in front
                    // of an XML string. If not, there is little
                    // the library can do to parse it, so a filename
                    // is assumed.
                    if (strpos(trim($source), "<") === false) {
                        if (is_readable($source)) {
                            // If $source is a file location, try to load it.
                            $this->fileName = $source;
                            try {
                                if ($this->domDocument->load($this->fileName)) {
                                    $this->logger->debug('Loaded XML document at ' . $this->fileName);
                                } else {
                                    throw new Dom\Exception("dom-cannot-load-document-bad-file", array($this->fileName, "code" => 404));
                                }
                            } catch (DOMException $exception) {
                                $this->logger->error($exception->getMessage());
                                throw new Dom\Exception("dom-cannot-load-document", 404);
                            }
                        } else {
                            $this->logger->warning('File cannot be read: ' . $source, LOG_WARNING);
                            throw new Dom\Exception(sprintf(
                                'Cannot load document at %s (Current path: %s)',
                                $source,
                                realpath('.')
                            ));
                        }
                    } else {
                        if ($this->domDocument->loadHTML($source)) {
                            $this->logger->debug('Loaded HTML document at ' . realpath($source));
                            $this->domDocument->normalize();
                        } else {
                            throw new Dom\Exception(
                                'Cannot create DOM document from this source.'
                            );
                        }
                    }
                }
            }
        } catch (\DOMException $exception) {
            $this->logger->error($exception->getMessage());
            throw new Dom\Exception("dom-cannot-create-document");
        }
    }

    /*
     * Loads an XML file into the document.
     */

    public function load($file)
    {
        return $this->domDocument->load($file);
    }

    /**
     * Merge the data and the XML template into an HTML page.
     * @param  type          $xslDocument the template.
     * @return type
     * @throws Dom\Exception
     */
    public function transform($xslDocument)
    {
        if ($xslDocument->domDocument) {
            $processor = new \XsltProcessor();
            $processor->importStylesheet($xslDocument->domDocument);
            $htmlDomDocument = new self;
            $htmlDomDocument->domDocument = $processor->transformToDoc($this->domDocument);

            if (!$htmlDomDocument->domDocument) {
                throw new Dom\Exception('The XSL transformation has failed.');
            }
        } else {
            throw new Dom\Exception('There is no XSL template to transform with.');
        }

        try {
            return $htmlDomDocument->toString(false);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new Dom\Exception("dom-transform-failed");
        }
    }

    /*
     * 	@desc		Add node to document
     * 	@access		public
     * 	@param's
     * 				@param string $path			-> location where to insert the node(s)
     * 				@param string $name				-> tag name
     * 	  optional	@param string $value			-> ISO-8859-1 encoded node content
     * 	@return
     * 				(this)DOMNode | false
     */

    public function addNode($parentNode, $name, $value = false)
    {
        // if $parentNode is a string, it is likely to be
        // an XPath expression. If so, it is converted
        // into a DOMElement.
        if (is_string($parentNode)) {
            if ($parentNode == "/") {
                $parentNode = $this->domDocument;
            } else {
                $parentNode = $this->getNode($parentNode);
            }
        }

        if ($parentNode) {
            try {
                $newNode = $this->domDocument->createElement($name);
                if (!is_array($value) && !is_resource($value)) {
                    $newNode->appendChild(
                        $this->domDocument->createTextNode($value)
                    );
                }

                return $parentNode->appendChild($newNode);
            } catch (\DOMException $exception) {
                throw new Dom\Exception("Could not add node named '$name'.");
            }
        } else {
            throw new Dom\Exception("Parent node not found: " . $parentNode);
        }
    }

    /*
     * adds an array as a set of nodes.
     */

    public function addArray($parentNode, $sourceArray, $containerTag = null)
    {
        if (!count($sourceArray)) {
            return null;
        }

        // if there is a container tag specification,
        // the array is enclosed into it, majorly to
        // be used as a root element.
        if ($containerTag) {
            $sourceArray = array($containerTag => $sourceArray);
        }

        foreach ($sourceArray as $key => $value) {
            $newNode = $this->addNodeWithContent($parentNode, $key, $value);
        }

        // Return last node created.
        return $newNode;
    }

    private function addNodeWithContent($parentNode, $nodeName, $content)
    {
        // if $parentNode is a string, it is likely to be
        // an XPath expression. If so, it is converted
        // into a DOMElement.
        if (is_string($parentNode)) {
            $parentNode = $this->getNode($parentNode);
        }

        // If the name of the node is a number, which is illegal,
        // the node is assigned a standard name.
        if (is_int($nodeName)) {
            $nodeName = 'item';
        }

        // Remove the namespace from the properties' names.
        if ($nodeName{0} == "\x00") {
            $nodeName = substr($nodeName, strpos($nodeName, "\x0",1) + 1);
        }

        // Add the content.
        $newNode = null;
        if (is_array($content)) {
            $newNode = $this->addNode($parentNode, $nodeName);
            $newNode = $this->addArray($newNode, $content);
        } elseif (is_object($content)) {
            $newNode = $this->addNode($parentNode, $nodeName);
            $newNode = $this->addObject($newNode, $content);
        } else {
            $newNode = $this->addNode($parentNode, $nodeName, $content);
        }

        return $newNode;
    }

    public function addObject($parentNode, $source, $containerTag = null)
    {
        $newNode = null;

        // If there is a container tag, a new node is added and the parent node
        // is changed in order to get the right hierarchy.
        if ($containerTag) {
            $parentNode = $this->addNode($parentNode, $containerTag);
        }

        foreach ((array) $source as $key => $value) {
            $newNode = $this->addNodeWithContent($parentNode, $key, $value);
        }

        // Return last node created.
        return $newNode;
    }

    /*
     * Copies nodes from their origin to a specified node.
     *
     * $target: DOMNode or XPath expression stating the target node for the copy.
     * $sourceNodes: DOMNodeList with the nodes to be copied. Can be result from getNodes().
     */

    public function copyNodes($target, DOMNodeList $sourceNodes)
    {
        if ($sourceNodes) {
            // If $target is string, try to parse as a XPath expression.
            $targetNode = is_string($target) ? $this->getNode($target) : $target;
            if ($targetNode) {
                // Import nodes into document and then add them to the target node.
                foreach ($sourceNodes as $nodeToImport) {
                    try {
                        $nodeToAppend = $this->domDocument->importNode($nodeToImport, true);
                        $targetNode->appendChild($nodeToAppend);
                    } catch (\Exception $exception) {
                        $this->logger->error($exception->getMessage());
                        throw new Dom\Exception($exception);
                    }
                }

                return $targetNode;
            } else {
                throw new Dom\Exception("dom-missing-target-node");
            }
        } else {
            return null;
        }
    }

    /*
     * 	@desc		Removes node(s) from a document
     * 	@access		public
     * 	@param's
     * 				@param string $path->XPath expression where the nodes are to be removed
     *
     * 	@return
     * 				DOMElement | false
     */

    public function removeNodes($path)
    {
        $nodesToRemove = $this->getNodes($path);
        if ($nodesToRemove) {
            for ($index = 0; $index < $nodesToRemove->length; $index++) {
                $nodeToRemove = $nodesToRemove->item($index);
                $nodeToRemove->parentNode->removeChild($nodeToRemove);
            }
        }
    }

    /*
     * Obtains a node through a XPath expression
     * and returns the DOMElement node. If more than
     * one node is found, the first one is returned.
     */

    public function getNode($path)
    {
        $nodes = $this->getNodes($path);
        if ($nodes && ($nodes->length > 0)) {
            return $nodes->item(0);
        }

        return null;
    }

    /*
     * 	@desc		get node(s) from XPath specific location of DOMDocument
     * 	@access		public
     * 	@param's
     * 				@param string $path		-> location where to get the node(s)
     * 	@return
     * 				XPathObject | false
     */

    public function getNodes($path)
    {
        $domXPath = new \DOMXpath($this->domDocument);
        // TODO: Swap the lines below for version 5.1.
        //			$nodes = $domXPath->evaluate($path);
        $nodes = $domXPath->query($path);
        if ($nodes) {
            return ($nodes->length > 0) ? $nodes : null;
        } else {
            throw new Exception('Bad XPath expression: ' . $path);
        }
    }

    /*
     * 	@desc		Get value of the content node select with XPath
     * 	@access		public
     * 	@param's
     * 				@param string $path
     * 	@return
     * 				true | false
     */

    public function getNodeContent($path)
    {
        $node = $this->getNode($path);
        if ($node) {
            switch (get_class($node)) {
                case "DOMAttr" :
                    $content = $node->value;
                    break;
                case "DOMElement" :
                    $content = $node->nodeValue;
                    break;
            }

            return $content;
        } else {
            return null;
        }
    }

    /*
     * 	@desc		Set value to the content node selected with XPath
     * 	@access		public
     * 	@param's
     * 				@param string $path
     * 				@param string $value		-> value of the content node
     * 	@return
     * 				true | false
     */

    public function setNodeContent($path, $value)
    {
        $node = $this->getNode($path);
        if ($node) {
            while ($node->hasChildNodes()) {
                $node->removeChild($node->firstChild);
            }
            $node->appendChild($this->domDocument->createTextNode($value));
        }
    }

    /*
     * 	@desc		get the number of nodes from a XPath
     * 	@access		public
     * 	@param's
     * 				@param string $path		-> location where to get the node(s)
     * 	@return
     * 				XPathObject | false
     */

    private function countNodes($path)
    {
        $nodes = $this->getNodes($path);
        if ($nodes) {
            return count($nodes->length);
        }

        return 0;
    }

    /*
     * Sets a node's attribute to the specified
     * value. If the attribute does not exist,
     * it is created.
     */

    public function setAttribute($node, $name, $value)
    {
        if (is_string($node)) {
            $node = $this->getNode($node);
        }

        if ($node) {
            $node->setAttribute($name, $value);
        }
    }

    /*
     * Returns the value of a node's attribute.
     */

    public function getAttribute($node, $name)
    {
        if (is_string($node)) {
            $node = $this->getNode($node);
        }

        if ($node) {
            return $node->hasAttribute($name) ?
                    $node->getAttribute($name) :
                    null;
        } else {
            return null;
        }
    }

    /*
     * Removes an attribute from a node.
     */

    public function removeAttribute($node, $name)
    {
        if (is_string($node)) {
            $node = $this->getNode($node);
        }

        if ($node) {
            $node->removeAttribute($name);
        } else {
            throw new Dom\Exception('Missing target node.');
        }
    }

    /*
     * Converts the DOM structure to a string. If 'tidy'
     * extension is installed, it is used. If not, the
     * function relies on DOM's own capability.
     * $isXML instructs the function to treat the
     * input as XML instead of HTML.
     */
    public function toString($isXML = true)
    {
        // Keep the formatting value.
        $formatOutput = $this->domDocument->formatOutput;
        // Format if the output will be XML.
        $this->domDocument->formatOutput = $isXML;
        // Get the XML in a string.
        $xmlString = $isXML
            ? $this->domDocument->saveXML()
            : $this->domDocument->saveHTML()
        ;
        // Restore the output formatting value.
        $this->domDocument->formatOutput = $formatOutput;

        // If the document has been saved as HTML, tags that do not need closing
        // in HTML will be duplicated, as a result of the closing tag being
        // correctly interpreted by HTML browsers as another tag. For instance,
        // <br /> will be converted to <br></br>, which the browser will
        // interpret as two <br> tags.
        if (!$isXML) {
            $xmlString = preg_replace('#></(?:br)>#', ' />', $xmlString);
        }

        return $xmlString;
    }

}
