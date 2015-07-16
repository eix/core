<?php

namespace Eix\Services\Data\Responders;

use Eix\Core\Exception;
use Eix\Core\Responders\Restricted;
use Eix\Core\Responses\Http;
use Eix\Core\Responses\Http\Html;
use Eix\Core\Responses\Http\Redirection;
use Eix\Services\Data\Entity;
use Eix\Services\Data\Factory;
use Eix\Services\Net\Http\BadRequestException;
use Eix\Services\Net\Http\NotFoundException;

/**
 * A responder used to manage the contents of a collection of entities.
 */
abstract class CollectionManager extends CollectionBrowser implements Restricted
{
    private $collectionBrowser;

    // Whether null values are ignored when storing. By default it is false,
    // which means that null values will set their associated field to null.
    protected $nullsIgnoredOnStore = false;

    /**
     * Get this collection manager's underlying collection browser.
     *
     * @return CollectionBrowser
     */
    protected function getCollectionBrowser()
    {
        if (empty($this->collectionBrowser)) {
            throw new Exception('No collection browser set for ' . get_called_class());
        }

        return $this->collectionBrowser;
    }

    /**
     * Establish this collection manager's underlying collection browser.
     *
     * @param \Eix\Services\Data\Responders\CollectionBrowser $collectionBrowser
     */
    protected function setCollectionBrowser(CollectionBrowser $collectionBrowser)
    {
        $this->collectionBrowser = $collectionBrowser;
    }

    /**
     * The default factory defaults to the collection browser's.
     *
     * @return Factory
     */
    public function getDefaultFactory()
    {
        return $this->getCollectionBrowser()->getDefaultFactory();
    }

    /**
     * @see CollectionBrowser::getCollectionName;
     *
     * Lacking polymorphism and PHP 5.4's traits, this technique allows using
     * both an implementation of the collection browser, and the implementation
     * of the collection manager.
     */
    public function getCollectionName()
    {
        return $this->getCollectionBrowser()->getCollectionName();
    }

    /**
     * @see CollectionBrowser::getCollectionName;
     *
     * Lacking polymorphism and PHP 5.4's traits, this technique allows using
     * both an implementation of the collection browser, and the implementation
     * of the collection manager.
     */
    public function getItemName()
    {
        return $this->getCollectionBrowser()->getItemName();
    }

    /**
     * @see CollectionBrowser::getEntity;
     *
     * Lacking polymorphism and PHP 5.4's traits, this technique allows using
     * both an implementation of the collection browser, and the implementation
     * of the collection manager.
     */
    protected function getEntity($id)
    {
        return $this->getCollectionBrowser()->getEntity($id);
    }

    /**
     * @see CollectionBrowser::getEntities;
     *
     * Lacking polymorphism and PHP 5.4's traits, this technique allows using
     * both an implementation of the collection browser, and the implementation
     * of the collection manager.
     */
    protected function getEntities($view = null)
    {
        return $this->getCollectionBrowser()->getEntities($view);
    }

    /**
     * Destroys the selected entity.
     */
    protected function destroyEntity($id)
    {
        return $this->getFactory()->findEntity($id)->destroy();
    }

    /**
     * Updates the entity with the new data in the request and stores it.
     *
     * @return Entity the stored entity
     */
    protected function storeEntity()
    {
        $request = $this->getRequest();
        $id = $request->getParameter('id');
        // Try to find an existing instance of the entity.
        $entity = NULL;
        try {
            $entity = $this->getFactory()->findEntity($id);
        } catch (NotFoundException $exception) {
            // Not found? Create it.
            $className = $this->getFactory()->getEntitiesClassName();
            $entity = new $className(array(
                'id' => $id,
            ));
        }


        // Get the data from the request.
        $dataFromRequest = $this->getEntityDataFromRequest();
        if ($this->nullsIgnoredOnStore) {
            // Remove unspecified values, so that they are not modified.
            $dataFromRequest = array_filter(
                $dataFromRequest,
                function ($value) {
                    return !is_null($value);
                }
            );
        }
        // Update the product from the request data.
        $entity->update($dataFromRequest);
        // Store the entity.
        $entity->store();

        return $entity;
    }

    /**
     * Returns all the data in the request that is associated with the entity
     * this collection manager deals with.
     * @return array
     */
    abstract protected function getEntityDataFromRequest();

    /**
     * Gets the current entity's data so that it can be set into the request.
     */
    public function getEntityData()
    {
        $entity = null;
        try {
            $entity = $this->getEntity(
                    $this->getRequest()->getParameter('id')
            );
        } catch (NotFoundException $exception) {
            // The entity does not exist, create a new one from the data in the
            // request.
            $entity = $this->getEntityClass()->newInstanceArgs(
                    $this->getEntityDataFromRequest()
            );
        }

        return $entity->getFieldsData();
    }

    /**
     * GET /{collection}[/:operation]
     *
     * @return Html
     */
    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');

        $response = null;
        if (empty($id)) {
            $response = parent::httpGetForHtml();
        } else {
            // The 'new' pseudo-ID indicates a new entity. Therefore, the ID
            // remains empty.
            $entity = ($id != 'new') ? $this->getEntity($id) : null;
            $response = $this->getEditionResponse($entity);
        }
        switch ($id) {
            case 'new':
                break;
            default:
       }

        return $response;
    }

    /**
     * POST /{collection}[/:id]
     *
     * @return Html
     */
    public function httpPostForHtml()
    {
        $id = $this->getRequest()->getParameter('id');
        $operation = $this->getRequest()->getParameter('operation');

        $response = null;
        switch ($operation) {
            case 'remove_selected':
                $response = $this->httpPostDeleteForHtml();
                break;
            case 'store':
            case 'save':
                $response = $this->getUpdatedEntityResponse();
                break;
            default:
                throw new BadRequestException(
                    "Operation '{$operation}' not recognised."
                );
        }

        return $response;
    }

    public function httpGetDeleteForHtml()
    {
        return $this->getDeletionConfirmationResponse(
            // There is only one ID, wrap it in an array.
            (array) $this->getRequest()->getParameter('id')
        );
    }

    /**
     * POST /{collection}/delete[/:id]
     *
     * @return Html
     */
    public function httpPostDeleteForHtml()
    {
        $isConfirmed = $this->getRequest()->getParameter('confirm') == 1;

        $response = null;
        if ($isConfirmed) {
            // If the operation has been confirmed, proceed.
            $response = $this->getDeletionResponse(
                $this->getRequest()->getParameter('ids')
            );
        } else {
            // If the operation is not confirmed, request confirmation.
            $selectedIds = $this->getSelectedIds();
            $response = $this->getDeletionConfirmationResponse($selectedIds);
        }

        return $response;
    }

    /**
     * Returns a response which displays a confirmation page for a batch
     * process.
     * @param string $actionId the ID of the action to perform. This will be
     * part of the URL.
     * @param array $selectedIds the IDs onto which the action would be
     * performed.
     * @return Http
     */
    protected function getBatchActionConfirmationResponse($actionId, array $selectedIds)
    {
        $response = null;

        if (empty($selectedIds)) {
            // No IDs, so redirect to the index page.
            $response = $this->getIndexRedirectionResponse();
        } else {
            // There are IDs, so display the confirmation page.
            $response = $this->getHtmlResponse();
            $collectionName = $this->getCollectionName();
            $response->setTemplateId(sprintf('%s/%s',
                $collectionName,
                $actionId
            ));
            $response->setData($collectionName, $selectedIds);
        }

        return $response;
    }

    /**
     * Issue a response to a batch action request.
     *
     * @param callback $actionFunction the function that will be executed for
     * each of the selected IDs.
     * @param array $selectedIds the IDs on which the batch process must be
     * executed.
     */
    protected function getBatchActionResponse($actionFunction, array $selectedIds)
    {
        $response = $this->getIndexRedirectionResponse();

        $successfullyUsedIds = array();
        foreach ($selectedIds as $id) {
            try {
                call_user_func($actionFunction, $id);
                $successfullyUsedIds[] = $id;
            } catch (NotFoundException $exception) {
                $response->addErrorMessage(sprintf(_('%s %s was not found.'),
                    _($this->getItemName()),
                    $id
                ));
            }
        }

        // TODO: Create a Report with the successfully used IDs.

        $response->addNotice(sprintf(_('%d %s were affected.'),
            count($successfullyUsedIds), _($this->getCollectionName())
        ));

        return $response;
    }

    /**
     * Issue a response to a deletion process.
     */
    protected function getDeletionResponse(array $selectedIds)
    {
        return $this->getBatchActionResponse(
            array($this, 'destroyEntity'),
            $selectedIds
        );
    }

    /**
     * Issue a response to a deletion process.
     */
    protected function getDeletionConfirmationResponse(array $selectedIds)
    {
        return $this->getBatchActionConfirmationResponse(
                'delete',
                $selectedIds
        );
    }

    /**
     * Gets a redirection to the collection's index page, honouring the current
     * view.
     */
    private function getIndexRedirectionResponse()
    {
        $response = new Redirection($this->getRequest());
        $url = sprintf('/%s/', $this->getCollectionName());
        // If a particular view was being shown, honour it.
        $view = $this->getRequest()->getParameter('view');
        if (!empty($view)) {
            $url .= '?view=' . $view;
        }
        $response->setNextUrl($url);

        return $response;
    }

    /**
     * Return a response that shows an edition page.
     * @param  type $id the ID of the entity to edit, if any.
     * @return Html
     */
    protected function getEditionResponse(Entity $entity = null)
    {
        // Create a new HTML response.
        $response = $this->getHtmlResponse();
        // Indicate where is the template.
        $response->setTemplateId(sprintf(
            '%s/edit',
            $this->getCollectionName()
        ));
        // If there is an entity ID, load that entity in the response as well.
        if ($entity) {
            $response->setData(
                $this->getItemName(),
                $entity->getFieldsData()
            );
        }

        return $response;
    }

    /**
     * Updates an entity with the data in the request, and emits a response to
     * attest for that.
     */
    private function getUpdatedEntityResponse()
    {
        $response = $this->getEditionResponse();
        try {
            // Try to store an entity with the data in the request.
            $this->storeEntity();
            $response->addNotice(sprintf(
                _('%s correctly stored.'),
                ucfirst($this->getItemName())
            ));
        } catch (ValidatorException $exception) {
            // The entity could not be stored because of a validation error.
            // Add the error data to the response.
            $response->addErrorMessage(array(
                'validation' => $exception->getValidationStatus(),
            ));
        } catch (\Exception $exception) {
            // An unexpected error has occurred. Display the edition
            // page again.
            $response->addErrorMessage($exception->getMessage());
        }
        // Set the entity data into the response.
        $response->setData(
            $this->getItemName(),
            $this->getEntityData()
        );

        return $response;
    }

    /**
     * Find the IDs of the selected elements in a list.
     */
    protected function getSelectedIds()
    {
        $ids = array();
        foreach ($_REQUEST as $parameter => $value) {
            $matches = array();
            preg_match('/select_(.*)/', $parameter, $matches);
            if ($matches) {
                $ids[] = $matches[1];
            }
        }

        return $ids;
    }


    /**
     * Override the original template identifier retrieval for entity edit
     * pages.
     * @return string
     */
    protected function getViewTemplateId()
    {
        return sprintf(
            '%s/edit',
            $this->getCollectionName()
        );
    }
}
