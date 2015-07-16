<?php

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validator;

/**
 * Asserts that the value is a validly uploaded file.
 */
class PostedFileValidator implements Validator
{
    protected $failures = array();

    public function isValid($value)
    {
        // Check that it looks like a file upload.
        if (is_array($value)) {
            $status = @$value['error'];
            $type = @$value['type'];
            $tmpName = @$value['tmp_name'];

            if ($status == UPLOAD_ERR_NO_FILE) {
                // If there is no file, there's nothing to validate, so the file
                // is implicitly valid. If having a file is a requirement,
                // please use the NonEmptyValidator.
                return true;
            }

            if ($status != UPLOAD_ERR_OK) {
                switch ($status) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->failures[] = _('the file is too big');
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->failures[] = _('there is no temporary space');
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->failures[] = _('the file cannot be stored');
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->failures[] = _('an extension has prevented the upload');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->failures[] = _('the file was only partially uploaded');
                        break;
                }

                return false;
            }

            if (!is_readable($tmpName)) {
                $this->failures[] = _('the uploaded file cannot be read');

                return false;
            }
        } else {
            // Not a file, can't continue.
            return false;
        }

        // Only valid if there have been no failures.
        return empty($this->failures);
    }

    public function getFailMessage()
    {
        return ucfirst(implode(', ', $this->failures) . '.');
    }
}
