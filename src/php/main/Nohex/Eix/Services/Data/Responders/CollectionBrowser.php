<?php

namespace Nohex\Eix\Services\Data\Responders;

use Nohex\Eix\Core\Responders\Http as HttpResponder;
use Nohex\Eix\Core\Responses\Http\Html;
use Nohex\Eix\Core\Responses\Http\Html as HtmlResponse;
use Nohex\Eix\Services\Data\Entity;
use Nohex\Eix\Services\Data\Exception;
use Nohex\Eix\Services\Data\Factory;

/**
 * A responder used to browse the contents of a collection of entities.
 */
abstract class CollectionBrowser extends HttpResponder
{
    private static $factories;

    abstract public function getDefaultFactory();

    public function getCollectionName()
    {
        return static::COLLECTION_NAME;
    }

    public function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function getEntityClass()
    {
        return new ReflectionClass(static::ITEM_CLASS);
    }

    protected function getEntity($id)
    {
        // Fetch the entity with that identifier.
        return $this->getFactory()->findEntity($id);
    }

    protected function getEntities($view = NULL)
    {
        return $this->getFactory()->getAll();
    }

    /**
     * Provide a default HTML response. To be overriden if a standard HTML
     * response is not adequate.
     *
     * @return \Nohex\Eix\Services\Data\Responders\HtmlResponse
     */
    protected function getHtmlResponse()
    {
        return new HtmlResponse($this->getRequest());
    }

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /**
     * GET /{collection}[/:id]
     * @return Html
     */
    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $view = $this->getRequest()->getParameter('view');

        if ($id) {
            $entity = $this->getEntity($id);
            if ($entity) {
                $response = $this->getViewResponse($entity);
            } else {
                throw new Exception('No entity found for identifier ' . $id);
            }
        } else {
            // Get the entities to be displayed.
            $entities = $this->getEntities($view);
            // Create a Response.
            $response = $this->getHtmlResponse();
            // Set the index template.
            $response->setTemplateId(sprintf(
                            '%s/index', $this->getCollectionName()
            ));
            // Load the entities' data into the response.
            $response->setData(
                    $this->getCollectionName(), $entities
            );
            // Append a descriptive title.
            $response->appendToTitle(ucfirst($this->getCollectionName()));
        }

        // Report which set of data is currently being shown.
        $response->setData('page', array('view', $view));

        return $response;
    }

    /**
     * Get an entity's displayable data.
     * @param  string $id the identifier of the entity to get the data from.
     * @return array
     */
    protected function getForDisplay($id)
    {
        return $this->getFactory()->findEntity($id)->getForDisplay();
    }

    /**
     * Get the factory that supplies entities to the collection browser.
     *
     * @return Factory
     * @throws Exception
     */
    public final function getFactory()
    {
        $calledClass = get_called_class();
        if (empty(self::$factories[$calledClass])) {
            self::$factories[$calledClass] = $this->getDefaultFactory();
        }

        return self::$factories[$calledClass];
    }

    /**
     * Set a factory to supply entities to the collection browser.
     *
     * @param \Nohex\Eix\Services\Data\Responders\Factory $factory
     */
    public static function setFactory(Factory $factory)
    {
        $calledClass = get_called_class();
        self::$factories[$calledClass] = $factory;
    }

    /**
     * Generate a response that displays an entity's data.
     *
     * @param  \Nohex\Eix\Services\Data\Entity $entity
     * @return Response
     */
    protected function getViewResponse(Entity $entity)
    {
        $response = $this->getHtmlResponse();
        $response->setTemplateId($this->getViewTemplateId());
        $response->setData(
                $this->getItemName(),
                $entity->getFieldsData()
        );
        $response->appendToTitle(sprintf(
            _('%s %s'),
            ucfirst($this->getItemName()),
            $entity->getId()
        ));

        return $response;
    }

    /**
     * Get the template identifier for entity view pages.
     * @return string
     */
    protected function getViewTemplateId()
    {
        return sprintf(
            '%s/view',
            $this->getCollectionName()
        );
    }
}
