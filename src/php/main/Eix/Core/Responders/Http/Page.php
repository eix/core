<?php
/**
 * A responder that emits HTML pages, and relies on their templates being in the
 * appropriate locations:
 * data/pages/[locale]/[section]/[action].xsl
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Application;
use Eix\Core\Responders\Http as HttpResponder;

class Page extends HttpResponder
{
    protected $section;
    private $page;

    public function getSection()
    {
        if (empty($this->section)) {
            $this->section = $this->getRequest()->getParameter('section');
            if (empty($this->section)) {
                $this->section = Application::getSettings()->routes->defaults->section;
            }
        }

        return $this->section;
    }

    public function getPage()
    {
        if (empty($this->page)) {
            $this->page = $this->getRequest()->getParameter('page');
            if (empty($this->page)) {
                $this->page = Application::getSettings()->routes->defaults->page;
            }
        }

        return $this->page;
    }

    public function setPage($page)
    {
        $this->page = $page;
        // Refresh the template ID with the new page.
        $this->response->setTemplateId(sprintf('%s/%s',
            $this->getSection(),
            $this->getPage()
        ));
    }

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /*
     * Overrides the standard HTTP response obtention method with one that
     * uses the template ID to set the location of the template.
     */
    public function httpGetForHtml()
    {
        if (empty($this->response)) {
            $this->response = new \Eix\Core\Responses\Http\Html($this->getRequest());
            // Set the page to update the template ID.
            $this->setPage($this->page);
        }

        return $this->response;
    }

}
