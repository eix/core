<?php

namespace Nohex\Eix\Services\Data\Validators;

/**
 * Asserts that the value is a validly uploaded image.
 */
class PostedImageValidator extends PostedFileValidator
{
    private $acceptedMimeTypes = array(
        'image/png',
        'image/gif',
        'image/jpeg',
    );

    public function isValid($value)
    {
        if (parent::isValid($value)) {
            // Check the MIME type.
            if (!in_array(@$value['type'], $this->acceptedMimeTypes)) {
                $this->failures[] = _('the uploaded file is not a recognised image');

                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}
