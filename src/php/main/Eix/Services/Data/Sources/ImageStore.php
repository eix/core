<?php

namespace Eix\Services\Data\Sources;

use Eix\Core\Application;
use Eix\Services\Net\Http\NotFoundException;
use Eix\Services\Data\Source as DataSource;
use Eix\Services\Log\Logger;

/**
 * Maintains an image storage facility.
 */
class ImageStore implements DataSource
{
    private static $instances = array();
    private $baseLocation;
    private $alternativeImageSizes = array();

    public static function getInstance($collection)
    {
        if (!(@self::$instances[$collection] instanceof self)) {
            self::$instances[$collection] = new self($collection);
        }

        return self::$instances[$collection];
    }

    /**
     * Creates a data source.
     *
     * @param string $collectionName the collection or group of objects the
     * images are part of.
     * @param \Eix\Core\Settings $settings the data source configuration. Of special
     * consideration is the
     */
    protected function __construct($collectionName, $settings = null)
    {
        if ($settings == null) {
            $settings = Application::getCurrent()->getSettings()->data->sources->imageStore;
        }

        $this->baseLocation = $settings->locations->$collectionName;

        // Set the collection.
        $this->collection = $collectionName;
    }

    public function create(array $data)
    {
        $id = @$data['id'];
        if (!$id) {
            throw new \InvalidArgumentException(
            'The image does not have an ID.'
            );
        }

        $this->update($id, $data);

        return $id;
    }

    public function retrieve($id)
    {
        Logger::get()->debug("Retrieving image {$id}...");

        $location = $this->getAssetLocation($id);
        if (!is_readable($location)) {
            throw new NotFoundException("No image found in {$location}.");
        }

        return array(
            'id' => $id,
            'location' => $location,
        );
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        $iterator = new DirectoryIterator($this->baseLocation);

        $images = array();
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $images[] = array(
                    'id' => $item->getFilename(),
                    'location' => $item->getPathname(),
                );
            }
        }

        return $images;
    }

    public function update($id, array $data)
    {
        Logger::get()->debug("Storing image {$id}...");

        $sourceLocation = @$data['location'];
        if ($sourceLocation) {

            $targetLocation = $this->getAssetLocation($id);

            $result = true;
            if ($sourceLocation != $targetLocation) {
                // Ensure the location is available.
                if (!is_dir($targetLocation)) {
                    @unlink($targetLocation);
                    mkdir($targetLocation, 0700, true);
                }
                // The image is stored to its new location in its original
                // form.
                $result = copy($sourceLocation, $targetLocation . '/original');
                // The image is then stored in every size used in the application, as
                // resizing them on the fly is very expensive.
                foreach ($this->alternativeImageSizes as $targetSize) {
                    $image = new \Imagick($sourceLocation);
                    $image->setImageFormat('png');
                    // Resize the image.
                    $image->resizeImage(
                            $targetSize, $targetSize, \Imagick::FILTER_CATROM, 1, true
                    );
                    // Apply rounder corners.
                    $image->roundCorners($targetSize / 18, $targetSize / 18);
                    // Overlay the image on the background.
                    // Save the results.
                    $image->writeImage($targetLocation . "/{$targetSize}");
                }
            }
        } else {
            throw new \InvalidArgumentException(
            'There is no image to store.'
            );
        }

        if ($result) {
            Logger::get()->debug('Stored.');
        } else {
            throw new Exception('The image could not be stored.');
        }

        return $result;
    }

    public function destroy($id)
    {
        Logger::get()->debug("Deleting image {$id}...");

        unlink($this->getAssetLocation($id));

        Logger::get()->debug('Deleted.');

        return true;
    }

    public function getAssetLocation($id)
    {
        return $this->baseLocation . '/' . $id;
    }

    public function setAlternativeImageSizes($alternativeImageSizes)
    {
        $this->alternativeImageSizes = $alternativeImageSizes;
    }

}
