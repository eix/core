<?php

namespace Nohex\Eix\Services\Data\Responders;

use Nohex\Eix\Core\Request;
use Nohex\Eix\Core\Responses\Http\Html as HtmlResponse;
use Nohex\Eix\Core\Responders\Http as HttpResponder;

/**
 * A responder used to browse the contents of a collection of entities.
 */
abstract class CollectionBrowser extends HttpResponder
{
    /**
     * Returns the name of the collection this list deals with. It usually is
     * the plural of the item name.
     */
    abstract public function getCollectionName();

    /**
     * Returns the name of an item this list is composed of. It usually is the
     * singular of the collection name.
     */
    abstract public function getItemName();

    /**
     * Returns the entity with the given ID.
     * This function needs to be implemented to return the appropriate
     * entity.
     * @param  string                          $id the entity's ID.
     * @return \Nohex\Eix\Services\Data\Entity
     */
    abstract protected function getEntity($id);

    /**
     * This function needs to be implemented to return a set of entities.
     * @param string $view an optional filter name that the implementing
     * function parses to produce a particular view of the collection.
     * @return \Nohex\Eix\Services\Data\Entity[] the resulting entities
     */
    abstract protected function getEntities($view = null);

    /**
     * Returns an HTML response. This function is meant to be overriden in
     * descending classes, when a plain HTML response is not enough.
     *
     * @param \Nohex\Eix\Core\Request $request
     */
    protected function getHtmlResponse(Request $request)
    {
        return new HtmlResponse($request);
    }

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /**
     * GET /{collection}[/:id]
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $view = $this->getRequest()->getParameter('view');

        if ($id) {
            $entity = $this->getEntity($id);
            $response = $this->getHtmlResponse($this->getRequest());
            $response->setTemplateId(sprintf('%s/edit',
                $this->getCollectionName()
            ));
            $response->setData(
                $this->getItemName(),
                $entity->getFieldsData()
            );
            $response->appendToTitle(
                sprintf(_('%s %s'),
                    ucfirst($this->getItemName()),
                    $id
                )
            );
        } else {
            // Get the entities to be displayed.
            $entities = $this->getEntities($view);
            // Create a Response.
            $response = $this->getHtmlResponse($this->getRequest());
            // Set the index template.
            $response->setTemplateId(sprintf('%s/index',
                $this->getCollectionName()
            ));
            // Load the entities' data into the response.
            $response->setData(
                $this->getCollectionName(),
                $entities
            );
            // Append a descriptive title.
            $response->appendToTitle(ucfirst($this->getCollectionName()));
        }

        // Report which set of data is currently being shown.
        $response->setData('page', array('view', $view));

        return $response;
    }
}
