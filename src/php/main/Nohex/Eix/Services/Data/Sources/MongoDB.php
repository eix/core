<?php

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Services\Data\Entity;
use Nohex\Eix\Core\Application;
use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Data\Source as DataSource;

/**
 * Manages data from a MongoDB database.
 */
class MongoDB implements DataSource
{
    private static $instances = array();
    private static $connector;
    private $collection;

    public static function getInstance($collection)
    {
        if (!(@self::$instances[$collection] instanceof self)) {
            self::$instances[$collection] = new self($collection);
        }

        return self::$instances[$collection];
    }

    public static function setConnector($connector)
    {
        self::$connector = $connector;
    }

    public static function getConnector($settings = null)
    {
        if (empty(self::$connector)) {
            // If there is no Mongo connector, raise a new one.
            self::$connector = new \Mongo(
                sprintf('mongodb://%s:%s@%s:%d',
                    $settings->user,
                    $settings->password,
                    $settings->host,
                    $settings->port
                ),
                array(
                    'db' => $settings->databaseName,
                )
            );
        }

        return self::$connector;
    }

    /**
     * Creates a data source based off a MongoDB database.
     *
     * @param string                   $collectionName the MongoDB collection name.
     * @param \Nohex\Eix\Core\Settings $settings       the data source configuration.
     */
    protected function __construct($collectionName, $settings = null)
    {
        if ($settings == null) {
            $settings = Application::getCurrent()->getSettings()->data->sources->mongodb;
        }

        // Get a Mongo instance.
        $mongo = self::getConnector($settings);
        // Select the database.
        $database = $mongo->selectDB($settings->databaseName);
        // Select the collection.
        $this->collection = $database->selectCollection($collectionName);
    }

    public function create(array $data)
    {
        self::convertToMongo($data);

        // Ensure there is an ID that can be returned.
        if (empty($data['_id'])) {
            $data['_id'] = new \MongoId;
        }

        // Record the creation time.
        $data['createdOn'] = new \MongoDate(time());

     Logger::get()->debug('Saving new record...');
        $result = $this->collection->insert($data, array('fsync' => true));

        $newId = $data['_id'];
        if ($newId instanceof \MongoId) {
            $newId = $data['_id']->__toString();
        }
     Logger::get()->debug('Stored new record ' . $newId);

        return $newId;
    }

    public function retrieve($id)
    {
        Logger::get()->debug("Retrieving record {$id}...");

        self::normaliseId($id);
        $result = $this->collection->findOne(array('_id' => $id));

        if ($result) {
            return self::convertFromMongo($result);
        } else {
            return null;
        }
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        Logger::get()->debug('Retrieving all records...');

        $result = $this->collection->find(
            $filter ?: array(),
            $fields ?: array()
        );

        // Sort by name, unless other fields are requested.
        $sortingField = @$options['sortingField'] ?: 'name';
        if ($sortingField) {
            $result->sort(array($sortingField => 1));
        }

        if ($result) {
            // Convert the iterator into an array, and convert it from Mongo.
            return self::convertFromMongo(iterator_to_array($result));
        } else {
            return null;
        }
    }

    public function update($id, array $data)
    {
        self::convertToMongo($data);

        $data['lastUpdatedOn'] = new \MongoDate(time());
        $result = $this->collection->save($data, array('upsert' => true));

     Logger::get()->debug('Updated record ' . $data['_id']);

        return $result;
    }

    public function destroy($id)
    {
        self::normaliseId($id);
        $result = $this->collection->remove(array('_id' => $id));

     Logger::get()->debug("Deleted record {$id}.");

        return true;
    }

    public function getCount(array $options = array())
    {
        $filter = array();

        // Check if a start date has been specified.
        $since = @$options['since'];
        if ($since instanceof \DateTime) {
            $filter['createdOn'] = array(
                '$gte' => new \MongoDate($since->getTimestamp()),
            );
        }

        return $this->collection->count($filter);
    }

    /**
     * Convert the various Mongo-based objects into PHP standard types, and
     * apply other conversions to standardise Mongo's idiosyncrasies.
     *
     * @param  array $data the data that will be passed to Mongo for storage.
     * @return array
     */
    private static function convertFromMongo(array $data)
    {
        // Move the Mongo _id field into an 'id' field.
        if (isset($data['_id'])) {
            $data['id'] = $data['_id'];
            unset($data['_id']);
        }

        foreach ($data as $name => &$value) {
            if (is_array($value)) {
                // Inner documents are converted too.
                $value = self::convertFromMongo($value);
            } elseif (is_object($value)) {
                switch (get_class($value)) {
                    case 'MongoId':
                        $value = $value->__toString();
                        break;
                    case 'MongoDate':
                        $value = new \DateTime('@' . $value->sec);
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * Convert entity data so that it better fits Mongo's idiosyncrasies.
     */
    private static function convertToMongo(array &$data)
    {
        // Move the ID field where Mongo expects it.
        if (isset($data['id'])) {
            // Create the _id field Mongo expects.
            $data['_id'] = $data['id'];
            // Remove the 'id' field from the data set that will be stored.
            unset($data['id']);
        }

        foreach ($data as $name => &$value) {
            // Ensure that all strings are UTF-8.
            if (is_string($value)) {
                // Convert all strings to UTF-8, otherwise Mongo complains.
                if (mb_detect_encoding($value) != 'UTF-8') {
                    $value = mb_convert_encoding($value, 'UTF-8');
                }
            } elseif ($value instanceof Entity) {
                // Only the presistable fields of Entities are kept.
                $value = $value->getFieldsData();
            } elseif (is_array($value)) {
                // The elements in an array are also checked in case they
                // contain sub-documents.
                self::convertToMongo($value);
            }
        }
    }

    /**
     * Checks whether the ID looks like a native Mongo one. If it does, it
     * converts it to MongoId.
     * @param \MongoId $id
     */
    private static function normaliseId(&$id)
    {
        if (preg_match('/^[0-9a-f]{24}$/', $id)) {
            $id = new \MongoId($id);
        }
    }

 }
