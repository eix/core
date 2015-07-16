<?php

namespace Eix\Core\Responses\Http;

use Eix\Services\Net\Http\NotFoundException;
use Eix\Services\Net\Http\BadRequestException;

/**
 * Response which outputs an image.
 */
class Image extends \Eix\Core\Responses\Http
{
    // The image as a string.
    private $image;
    // The image as a file name.
    private $fileName;

    /**
     * Sets the type of the image. Only needed if the image is in the form of
     * a file name.
     *
     * @param  string    $type the MIME type.
     * @throws Exception
     */
    public function setType($type)
    {
        switch ($type) {
            case 'png':
                $this->setContentType('image/png');
                break;
            case 'jpg':
            case 'jpeg':
                $this->setContentType('image/jpeg');
                break;
            default:
                throw new Exception("Unsupported image format: $type");
        }
    }

    public function issue()
    {
        // Make the image last in the cache for at least a day.
        $this->addHeader('Cache-Control', '3600');

        // Output headers.
        parent::issue();

        // Check whether the image needs to be resized.
        $this->checkForResize();

        if ($this->image instanceof \Imagick) {
            echo $this->image;
        } elseif ($this->fileName) {
            if (is_readable($this->fileName)) {
                readfile($this->fileName);
            } else {
                throw new NotFoundException("Image not found in {$this->fileName}");
            }
        } else {
            throw new BadRequestException('No image has been set.');
        }
    }

    /**
     * Creates the image from its binary stream.
     *
     * @param string $image the image contents.
     */
    public function setImage($image)
    {
        $this->image = \Imagick($image);
    }

    /**
     * Sets the name of the file that contains the image.
     *
     * @param string $fileName the file name
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        $this->setContentType(mime_content_type($this->fileName));
    }

    private function checkForResize()
    {
        $size = $this->getRequest()->getParameter('size');
        if ($size) {
            if (!$this->image instanceof Imagick) {
                $this->image = new \Imagick($this->fileName);
            }

            // Scale proportionally to
            $this->image->thumbnailImage($size, $size, true);
        }
    }
}
