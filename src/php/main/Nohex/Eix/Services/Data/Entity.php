<?php

namespace Nohex\Eix\Services\Data;

use Nohex\Eix\Services\Log\Logger;

/**
 * Structured representation of a set of related data that can be persisted.
 */
abstract class Entity
{
    protected $dataSource;
    protected $id;
    protected $fields = array();
    protected $fieldValidators = array();
    protected $isNew;

    /**
     * @param array   $data  the properties of the object.
     * @param boolean $isNew whether the data is fresh (true) or coming from a
     * previously persisted object (false).
     */
    public function __construct(array $data, $isNew = true)
    {
        $this->update($data);

        $this->isNew = $isNew;

        $this->fieldValidators = $this->getFieldValidators();
    }

    /**
     * Allow direct read access to fields (persistable properties).
     *
     * @param  string $field the field name
     * @return mixed
     */
    public function __get($field)
    {
        if (in_array($field, $this->getFields())) {
            return $this->$field;
        }
    }

    /**
     * Update an entity's data.
     *
     * @param array   $data     the data to update the entity with.
     * @param boolean $isAtomic whether a non-valid value cancels the update
     * operation. Set to false if the valid fields need to be kept in spite
     * of the invalid ones.
     */
    // FIXME: Optimise, perhaps moving the validator to a __set function.
    public function update(array $data, $isAtomic = true)
    {
        $validationStatus = array();
        // Check all values before modifying the object.
        foreach ($data as $name => $value) {
            $fieldValidationStatus = $this->validate($name, $value);
            if ($fieldValidationStatus) {
                $validationStatus[$name] = $fieldValidationStatus;
            } elseif (!$isAtomic) {
                // If the field is valid and the operation does not need
                // to be atomic, keep the value.
                $this->$name = $value;
            }
        }

        if (empty($validationStatus)) {
            // If the operation is not atomic, the valid values have
            // already been set. Otherwise, all the values can now
            // be safely assigned.
            if ($isAtomic) {
                // The fields' values are valid, the object can be updated.
                foreach ($data as $name => $value) {
                    // Check each value before modifying the object.
                    $this->$name = $value;
                }
            }
        } else {
            // If there is something in the validation status, the validation
            // failed. Load the validation status into an exception.
            $exception = new Validators\Exception;
            $exception->setValidationStatus($validationStatus);
            throw $exception;
        }

        // If the entity has an now an ID, register it with its Factory.
        $id = @$data['id'];
        if ($id && !$this->getFactory()->getEntity($id)) {
            $this->getFactory()->registerEntity($this);
        }
    }

    /**
     * Validates a field.
     *
     * @param string   $name       the field name
     * @param mixed    $value      the field value
     * @param string[] $validators custom validators for that field. If
     * not set, the entity's validators defined for that field will be used.
     * @return array an array of the invalid fields and the validations they
     * have failed.
     */
    // FIXME: Part of the fix of bringing each field into a Field object.
    protected function validate($name, $value, $validators = null)
    {
        if (empty($validators)) {
            $validators = @$this->fieldValidators[$name];
        }
        $failedValidations = array();

        if (!empty($validators)) {
            foreach ($validators as $type) {
             Logger::get()->debug("Validating $name with $type...");
                $validator = Validators::get($type);
                if (!$validator->isValid($value)) {
                 Logger::get()->debug('Failed!');
                    // The validation has failed, report its data.
                    $failedValidations[] = $validator->getFailMessage();
                }
            }
        }

        return $failedValidations;
    }

    /**
     * Obtains the data source that this entity uses if none other has been set.
     * This function is called from getDataSource() so that a data source is
     * available when it is needed, but not until it is.
     */
    abstract protected function getDefaultDataSource();

    /**
     * Returns the set of properties that need to be kept when the object is
     * stored.
     *
     * @return string[] the names of the class members that need to be stored.
     */
    abstract protected function getFields();

    /**
     * Returns a list of validators for each field that needs them.
     *
     * @return array
     */
    abstract protected function getFieldValidators();

    /**
     * Returns a list of all the entity's fields that can be displayed.
     *
     * @return mixed[] the fields and their values.
     */
    abstract public function getForDisplay();

    /**
     * Returns the factory instance that serves objects of the current class.
     * @return Factory
     */
    abstract protected function getFactory();

    /**
     * Sets the entity's field validators.
     * @param array $fieldValidators the field validators as an array
     * in the form: array(fieldName => array(validatorType1[, validatorType2]...))
     */
    public function setFieldValidators(array $fieldValidators)
    {
        $this->fieldValidators = $fieldValidators;
    }

    /**
     * Adds field validators to the existing ones.
     * @param array $fieldValidators the field validators as an array
     * in the form: array(fieldName => array(validatorType1[, validatorType2]...))
     */
    public function addFieldValidators(array $fieldValidators)
    {
        foreach ($fieldValidators as $fieldName => $validators) {
            $this->fieldValidators[$fieldName] = $validators;
        }
    }

    /**
     * Returns an array composed of all the persistable fields.
     *
     * @return array
     */
    public function getFieldsData()
    {
        $fieldsData = array();
        foreach ($this->getFields() as $field) {
            $fieldData = $this->$field;
            // If the field is an Entity, decode it.
            if ($fieldData instanceof self) {
                $fieldData = $fieldData->getFieldsData();
            } elseif (@reset($fieldData) instanceof Entity) {
                // If the field is an array of Entity, decode them all.
                $newFieldData = array();
                foreach ($fieldData as $key => $entity) {
                    $newFieldData[$key] = $entity->getFieldsData();
                }
                $fieldData = $newFieldData;
            }

            $fieldsData[$field] = $fieldData;
        }

        return $fieldsData;
    }

    public function getDataSource()
    {
        if (empty($this->dataSource)) {
            // Assign the data source, now that one is needed.
            $this->assignDataSource();
            // If, after assigning the data source, there is still nothing in
            // the property, then fail.
            if (empty($this->dataSource)) {
                throw new Exception('No datasource has been defined.');
            }
        }

        return $this->dataSource;
    }

    /**
     * Stores the object in the persistence layer.
     */
    public function store()
    {
        // Check whether the object has ever been stored.
        if ($this->isNew) {
         Logger::get()->debug('Storing new entity ' . get_class($this) . '...');
            // Create the record. Get an ID back.
            $this->id = $this->getDataSource()->create($this->getFieldsData());
            // Store this object in the appropriate factory for further use.
            $this->getFactory()->registerEntity($this);
        } else {
         Logger::get()->debug('Updating entity ' . get_class($this) . ":{$this->id}...");
            $this->getDataSource()->update($this->id, $this->getFieldsData());
        }

        // Once stored, the entity is no longer new.
        $this->isNew = false;
    }

    /**
     * Destroys all copies of the object, even the persisted ones.
     */
    public function destroy()
    {
        // Delete from the persistence layer.
        $this->getDataSource()->destroy($this->id);
        // Remove from the registry.
        $this->getFactory()->unregisterEntity($this);
        // Done, garbage collection should do the rest.
    }

    /**
     * Retrieves the entity's ID.
     *
     * @return string the entity's ID.
     */
    public function getId()
    {
        return $this->id;
    }
}
