<?php

namespace Nohex\Eix\Services\Data\Responders;

use Nohex\Eix\Core\Responders\Restricted;
use Nohex\Eix\Core\Responses\Http\Redirection;
use Nohex\Eix\Services\Net\Http\BadRequestException;
use Nohex\Eix\Services\Net\Http\NotFoundException;
use Nohex\Eix\Services\Data\Validators\Exception;

/**
 * A responder used to manage the contents of a collection of entities.
 */
abstract class CollectionManager extends CollectionBrowser implements Restricted
{
    /**
     * Destroys the selected entity.
     */
    abstract protected function destroyEntity($id);

    /**
     * Stores the current entity.
     */
    abstract protected function storeEntity();

    /**
     * Returns all the data in the request that is associated with the entity
     * this collection manager deals with.
     * @return array
     */
    abstract protected function getEntityDataFromRequest();

    /**
     * This function must return the ReflectionClass object of the type of
     * entities this collection deals with.
     * @return ReflectionClass
     */
    abstract protected function getEntityClass();

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
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    public function httpGetForHtml()
    {
        $id = $this->getRequest()->getParameter('id');

        switch ($id) {
            case 'new':
                // This pseudo-ID indicates a new entity which does not yet
                // have an ID.
                return $this->getEditionResponse();
            default:
                return parent::httpGetForHtml();
        }
    }

    /**
     * POST /{collection}[/:id]
     *
     * @return \Nohex\Eix\Core\Responses\Http\Html
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
     * @return \Nohex\Eix\Core\Responses\Http\Html
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
     * @return \Nohex\Eix\Core\Responses\Http
     */
    protected function getBatchActionConfirmationResponse($actionId, array $selectedIds)
    {
        $response = null;

        if (empty($selectedIds)) {
            // No IDs, so redirect to the index page.
            $response = $this->getIndexRedirectionResponse();
        } else {
            // There are IDs, so display the confirmation page.
            $response = $this->getHtmlResponse($this->getRequest());
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
     * @param  type                                $id the ID of the entity to edit, if any.
     * @return \Nohex\Eix\Core\Responses\Http\Html
     */
    private function getEditionResponse($id = null)
    {
        // Create a new HTML response.
        $response = $this->getHtmlResponse($this->getRequest());
        // Indicate where is the template.
        $response->setTemplateId(sprintf(
            '%s/edit',
            $this->getCollectionName()
        ));
        // If there is an entity ID, load that entity in the response as well.
        if ($id) {
            $response->setData(
                $this->getItemName(),
                $this->getEntity($id)
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
        $entityData = null;
        try {
            // Try to store an entity with the data in the request.
            $entityData = $this->storeEntity();
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

}
