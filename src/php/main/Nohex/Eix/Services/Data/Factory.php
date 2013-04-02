<?php

namespace Nohex\Eix\Services\Data;

use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Http\NotFoundException;
use Nohex\Eix\Services\Data\Source as DataSource;

/**
 * Provides means to manage a collection of Entity objects.
 */
abstract class Factory
{
    const ENTITIES_CLASS_NAME = '\\stdClass';

    protected $dataSource;

    /**
     * Factories are singletons, there can only be one instance per class.
     */
    protected static $instance;

    /**
     * Entity cache: all entities of the class this factory deals out
     * which have an ID are stored here.
     */
    protected static $entities = array();

    /**
     * Return the current factory instance. Please note the use of static
     * instead of self, which provides one instance per descendant.
     */
    public static function getInstance()
    {
        $class = get_called_class();

        // static:: doesn't quite work, so all instances are stored in Factory.
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class;
        }

        return self::$instance[$class];
    }

    /**
     * This function must be overridden to return the data source the factory
     * will use under normal conditions.
     */
    abstract protected function getDefaultDataSource();

    /**
     * Allows setting a data source other than the default one.
     *
     * @param Nohex\Eix\Services\Data\Source $dataSource The data source to
     * assign.
     */
    public function setDataSource(DataSource $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * Returns the data source this factory is fed from.
     *
     * @param  string                                     $id the entity's ID.
     * @return \Nohex\Eix\Services\Data\entitiesClassName
     */
    public function getDataSource()
    {
        if (empty($this->dataSource)) {
            // Assign the default data source, now that one is needed.
            $this->dataSource = $this->getDefaultDataSource();
            // If, after assigning the data source, there is still nothing in
            // the property, then fail.
            if (empty($this->dataSource)) {
                throw new Exception('No datasource has been defined.');
            }
        }

        return $this->dataSource;
    }

    /**
     * Returns the entity identified by the ID.
     *
     * @param  string                                     $id the entity's ID.
     * @return \Nohex\Eix\Services\Data\entitiesClassName
     */
    public function getEntity($id)
    {
        // static:: doesn't quite work, so all entities are stored in Factory.
        return @self::$entities[get_called_class()][$id];
    }

    /**
     * Returns the entity identified by the ID, retrieving it from the
     * persistence layer if need be.
     *
     * @param string $id     the entity's ID.
     * @param string $strict if true, it just checks for the existence of
     * the object.
     * @return \Nohex\Eix\Services\Data\entitiesClassName
     */
    public function findEntity($id)
    {
        Logger::get()->debug(sprintf(
            'Finding entity %s...',
            $id
        ));
        $entity = $this->getEntity($id);

        // If the entity is not registered, load it from the persistence layer.
        if (empty($entity)) {
            Logger::get()->debug(sprintf(
                'Entity %s is not cached. Retrieving...',
                $id
            ));
            $className = static::ENTITIES_CLASS_NAME;
            $data = $this->getDataSource()->retrieve($id);

            if (is_array($data)) {
                Logger::get()->debug(sprintf(
                    'Entity %s retrieved.',
                    $id
                ));
                $entity = new $className($data, false);
            } else {
                Logger::get()->debug(sprintf(
                    'Entity %s could not be retrieved.',
                    $id
                ));
                throw new NotFoundException("Entity {$className}:{$id} could not be retrieved.");
            }
        }

        return $entity;
    }

    /**
     * Keeps the selected entity in memory for quick access.
     */
    public function registerEntity(Entity $entity)
    {
        Logger::get()->debug('Registering entity ' . get_class($entity) . ":{$entity->id}...");
        self::$entities[get_called_class()][$entity->id] = $entity;
        Logger::get()->debug('Registered.');
    }

    /**
     * Drops the selected entity from memory.
     */
    public function unregisterEntity(Entity $entity)
    {
        Logger::get()->debug('Dropping entity ' . get_class($entity) . ":{$entity->id}...");
        unset(self::$entities[get_called_class()][$entity->id]);
        Logger::get()->debug('Dropped.');
    }

    /**
     * Returns all entities in the collection.
     *
     * @param array $filter the set of fields and values the data set needs to
     * adhere to.
     *
     * @return array
     */
    public function getAll($filter = null)
    {
        $className = static::ENTITIES_CLASS_NAME;
        $results = $this->getDataSource()->retrieveAll($filter);

        // Convert the resulting data to entities.
        $entities = array();
        foreach ($results as $data) {
            $id = @$data['id'];
            $entity = $this->getEntity($id);
            if (empty($entity)) {
                $entity = new $className($data);
            }
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Returns the number of entities currently stored.
     *
     * @param \DateTime $options data-source-specific options to count another
     * set of objects.
     * @return integer
     */
    public static function getCount(array $options = array())
    {
        return static::getInstance()->getDataSource()->getCount($options);
    }
}
