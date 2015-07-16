<?php

namespace Eix\Services\Net\Mail;

use Eix\Core\Application;

/**
 * Mail message abstraction.
 */
class Message
{
    private $sender;
    private $recipients = array();
    private $attachments = array();
    private $body;
    private $contentType = 'text/plain';
    private $hasAttachments = false;
    public $subject;
    public $charset = 'utf-8';

    public function setSender($address, $name = null)
    {
        $this->sender = new Address($address, $name);
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function addRecipient($address, $name = null, $type = "to")
    {
        if (!$this->getRecipient($address)) {
            $this->recipients[$address] = new Recipient($type, $address, $name);
        }
    }

    public function getRecipients($type = null)
    {
        if (is_null($type)) {
            return $this->recipients;
        } else {
            $recipientsToReturn = null;
            foreach ($this->recipients as $recipient) {
                if ($recipient->type == $type) {
                    $recipientsToReturn[] = $recipient;
                }
            }

            return $recipientsToReturn;
        }
    }

    public function getRecipient($address)
    {
        return isset($this->recipients[$address])
            ? $this->recipients[$address]
            : null
        ;
    }

    public function setBody($body, $isHTML = false)
    {
        if ($isHTML) {
            $this->contentType = 'text/html';
        }

        $this->body = $body;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /*
     * Adds an attachment.
     */

    public function addAttachment($name, $MIMEType, $content)
    {
        if (isset($this->attachments[$name])) {
            throw new Exception("The attachment identified by '{$name}' has already been set.");
        } else {
            $this->attachments[$name] = array("type" => $MIMEType, "content" => $content);
            $this->hasAttachments = true;
        }
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    /*
     * This function returns hasAttachments,
     * so the property can be protected
     * from writing.
     */

    public function isMultipart()
    {
        return $this->hasAttachments;
    }

    /*
     * Extracts files from a $_FILES-like parameter and
     * attaches them to the message.
     */

    public function attachPostFiles($files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                // Check whether file has been posted.
                if (is_uploaded_file($file['tmp_name'])) {
                    // Get file from its location.
                    $fileContents = file_get_contents($file['tmp_name']);
                    if ($fileContents) {
                        $this->addAttachment(
                                $file['name'], $file['type'], $fileContents
                        );
                    } else {
                        throw new Exception('Empty attachment');
                    }
                } else {
                    switch ($file['error']) {
                        case 0:
                            throw new Exception('Illegal file: ' . $file['name']);
                            break;
                        case 1:
                        case 2:
                            throw new Exception('File too large');
                            break;
                        case 3:
                            throw new Exception('File truncated');
                            break;
                        case 4:
                            // File slot is empty. Just skip it.
                            break;
                        default:
                            throw new EixMailMessageBadAttachmentException();
                            break;
                    }
                }
            }
        } else {
            throw new Exception('Unrecognised format');
        }
    }

    /**
     * Sends the current mail message.
     */
    public function send()
    {
        $settings = Application::getSettings()->mail;
        $mailTransport = new Transport(
            @$settings->smtp->host ? : 'localhost',
            @$settings->smtp->port ? : 25,
            @$settings->smtp->timeout ? : 15
        );

        $mailTransport->setUser(
            @$settings->smtp->user,
            @$settings->smtp->password
        );

        $mailTransport->addMessage($this);

        $mailTransport->send();
    }

}
