<?php

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Services\Data\Source as DataSource;
use Nohex\Eix\Services\Net\Http\NotFoundException;

/**
 * An immutable data source is a list of fixed entities. This class provides means
 * to implement such one.
 */
abstract class Immutable implements DataSource
{
    private static $instances = array();
    private static $data = array();

    public static function getInstance()
    {
        $class = get_called_class();
        if (!(@self::$instances[$class] instanceof static)) {
            self::$instances[$class] = new static;
        }

        return self::$instances[$class];
    }

    /**
     * The constructor makes sure that the list of entities is pre-loaded.
     */
    final private function __construct()
    {
        $class = get_called_class();
        // Load the entities into this class's space.
        foreach ($this->loadEntities() as $entityData) {
            // Index the entities by ID.
            self::$data[$class][$entityData['id']] = $entityData;
        }
    }

    /**
     * This method must implement the means to obtain the list of entities.
     */
    abstract protected function loadEntities();

    public function create(array $data)
    {
        throw new LogicException('No elements can be created through an immutable data source.');
    }

    public function retrieve($id)
    {
        $class = get_called_class();

        // Throw NotFoundException, to match DataSource's behaviour.
        if (!isset(self::$data[$class][$id])) {
            throw new NotFoundException(sprintf(
                'Entity "%s" does not exist.',
                $id
            ));
        }

        return self::$data[$class][$id];
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        // TODO: Implement filtes and field-based return.
        $class = get_called_class();

        return self::$data[$class];
    }

    public function update($id, array $data)
    {
        throw new LogicException('Elements from an immutable data source cannot be updated.');
    }

    public function destroy($id)
    {
        throw new LogicException('Elements from an immutable data source cannot be destroyed.');
    }

}
