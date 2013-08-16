<?php

namespace Nohex\Eix\Core\Responses\Http;

use Nohex\Eix\Core\Application;
use Nohex\Eix\Core\Exception;
use Nohex\Eix\Core\Requests\Http as HttpRequest;

/**
 * Response which outputs HTML.
 *
 * The HTML is obtained using XSL transformations on the view data, which
 * is represented by an XML document in this case.
 */
class Html extends \Nohex\Eix\Core\Responses\Http
{
    const CONTENT_TYPE = 'text/html';

    private $templateId;
    private $titleParts = array();

    public function __construct(HttpRequest $request = null)
    {
        $this->setContentType(self::CONTENT_TYPE);

        parent::__construct($request);

        // Set the initial title.
        try {
            $this->titleParts = array(Application::getCurrent()->getName());
        } catch (Exception $exception) {
            $this->titleParts = '';
        }
    }

    public function issue()
    {
        // Output headers.
        parent::issue();

        $this->setData('page', array(
            // Piece the title together.
            'title' => @implode(' — ', $this->titleParts),
            // Add information about the current template.
            'template' => str_replace(array('.', '/'), '_', $this->templateId),
            // Inform about the page's content type.
            'contentType' => $this->getContentType(),
            'url' => $this->getRequest() ? $this->getRequest()->getCurrentUrl() : '',
        ));

        // If there are status messages left in the session by a Redirection
        // response, fetch them...
        $statusMessages = @$_SESSION['messages']['status'];
        // ... remove them from the session...
        // ... and add them to the page status.
        if (is_array($statusMessages)) {
            unset($_SESSION['messages']['status']);
            $this->addData('status', $statusMessages);
        }

        // Provide a hook for any custom data to be added to the response before
        // the latter being issued.
        $this->setCustomData();

        // Now output the page.
        $medium = new Media\XslPage($this->templateId, $this->getData());
        $medium->render();
    }

    /**
     * Hook to add more data to the response.
     */
    protected function setCustomData()
    {
        // Override this function with any additional data the response needs.
    }

    /**
     * Sets the template id, consisting of the path and the file name, without
     * any extensions.
     * @param type $id the template id.
     */
    public function setTemplateId($id)
    {
        $this->templateId = $id;
    }

    /**
     * Appends a to the page title.
     *
     * @param string $titlePart
     */
    public function appendToTitle($titlePart)
    {
        $this->titleParts[] = $titlePart;
    }

}
