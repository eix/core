<?php

namespace Nohex\Eix\Services\Net\Mail;

use Nohex\Eix\Services\Log\Logger;
use Nohex\Eix\Services\Net\Socket;

/**
 * Eix SMTP mail transport.
 */
class Transport
{
    const SMTP_REPLY_CLOSING = 221;
    const SMTP_REPLY_DS_READY = 220;
    const SMTP_REPLY_OK = 250;
    const SMTP_REPLY_CHALLENGE = 334;
    const SMTP_REPLY_AUTHENTICATED = 235;
    const SMTP_REPLY_BEGIN_RESPONSE = 354;
    const EIXMAIL_IDENTIFIER = "EixMail/1.0";

    private $socket;
    private $messages;
    private $messageParts;
    private $boundary;
    private $host;
    private $headers;
    // Stantard SMTP port
    private $port = 25;
    private $domain;
    private $timeout = 15;
    private $user;
    private $password;
    private $contentTransferEncoding = '7bit';
    // This controls the behaviour of sendRecipients(). By default,
    // when a bad recipient is found, the message sending is stopped.
    private $haltAtBadRecipient = true;

    public function __construct($host = null, $port = null, $timeout = null)
    {
        // If no host is specified, try to assign one from Eix standard settings.
        if (!$host) {
            global $mailServer;
            $this->host = $mailServer;
        } else {
            $this->host = $host;
            if ($port) {
                $this->port = $port;
            }
            if ($timeout) {
                $this->timeout = $timeout;
            }
        }

        $this->boundary = md5(uniqid(self::EIXMAIL_IDENTIFIER));
        $this->setHeader("Mime-Version", "1.0");
    }

    /*
     * Adds a message to the messages array.
     */

    public function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /*
     * Obtains a message to the messages array.
     */

    public function getMessages($id = null)
    {
        if (is_null($id)) {
            return $this->messages;
        } else {
            return isset($this->messages[$id]) ? $this->messages[$id] : null;
        }
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setUser($user, $password = null)
    {
        $this->user = $user;
        if ($password) {
            $this->password = $password;
        }
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    /*
     * Sends a message
     */

    public function send()
    {
        if (is_array($this->messages)) {
            try {
                // Start communication.
                $this->connect();
                $this->handshake();
                $this->authenticate();

                foreach ($this->messages as $message) {
                    // Perform message-level checks.
                    $sender = $message->getSender();
                    if ($sender) {
                        $this->sendSender($sender);
                    } else {
                        throw new NoSenderException;
                    }

                    $subject = $message->subject;
                    if (trim($subject)) {
                        $this->setHeader("Subject", $subject);
                    } else {
                        throw new NoSubjectException;
                    }

                    $recipients = $message->getRecipients();
                    if (count($recipients) > 0) {
                        $this->sendRecipients($recipients);
                    } else {
                        throw new NoRecipientsException;
                    }

                    $this->sendData($message);
                 Logger::get()->debug('A message has been sent to ' . implode(", ", array_keys($recipients)));
                }

                // End communication.
                $this->quit();
            } catch (Exception $exception) {
                // Ensure communication is cut whatever the circumstances.
                $this->reset();
                $this->quit();
                throw $exception;
            }
        } else {
            throw new NoMessagesException;
        }
    }

    /*
     * Connects and gets welcome message.
     */

    private function connect()
    {
        $response = $this->getResponse();
        if ($this->getResponseCode($response) != self::SMTP_REPLY_DS_READY) {
            throw new Exception('Connection failed.');
        }
    }

    /*
     * Handshakes the mail host.
     */

    private function handshake()
    {
        $domain = $this->domain ? $this->domain : 'eix.nohex.com';

        if ($this->getResponseCode($this->sendCommand("EHLO {$domain}")) != self::SMTP_REPLY_OK) {
            throw new Exception('Mail transport handshaking failure.');
        }
    }

    /*
     * Authenticates the user in the mail server.
     */

    private function authenticate()
    {
        // If there is a user and a password...
        if (isset($this->user) && isset($this->password)) {
            // ... initiate authentication stating wich type to use.
            if ($this->getResponseCode($this->sendCommand("AUTH LOGIN")) == self::SMTP_REPLY_CHALLENGE) {
                // Send user.
                $user = base64_encode($this->user);
                if ($this->getResponseCode($this->sendCommand($user)) == self::SMTP_REPLY_CHALLENGE) {
                    // User OK. Send password.
                    $password = base64_encode($this->password);
                    if ($this->getResponseCode($this->sendCommand($password)) == self::SMTP_REPLY_AUTHENTICATED) {
                        return;
                    }
                }
            }
            throw new AuthenticationException;
        }
    }

    /*
     * Communicates sender to SMTP server.
     */

    private function sendSender($sender)
    {
        if ($this->getResponseCode($this->sendCommand(sprintf('MAIL FROM: <%s>', $sender->getAddress()))) != self::SMTP_REPLY_OK) {
            throw new Exception('Bad sender: ' . $sender->toString());
        } else {
            $this->setHeader('From', $sender->toString());
        }
    }

    /*
     * Communicates recipients to SMTP server.
     */

    private function sendRecipients($recipients)
    {
        foreach ($recipients as $recipient) {
            if ($this->getResponseCode($this->sendCommand(sprintf('RCPT TO: <%s>', $recipient->getAddress()))) != self::SMTP_REPLY_OK) {
                if ($this->haltAtBadRecipient) {
                    throw new Exception('Bad recipient: ' . $rcptTo);
                }
            }
            $this->setHeader(ucfirst($recipient->getType()), $recipient->toString());
        }
    }

    /*
     * Sends message to SMTP server.
     */

    private function sendData($message)
    {
        if ($this->getResponseCode($this->sendCommand("DATA")) == self::SMTP_REPLY_BEGIN_RESPONSE) {
            if ($message->isMultipart()) {
                $contentType = "multipart/mixed;\n boundary=\"{$this->boundary}\";";
            } else {
                $contentType = "{$message->getContentType()};\n charset=\"{$message->charset}\";";
            }
            $this->setHeader("Content-Type", $contentType);

            // Start data string with headers.
            $data = $this->getComposedHeaders($message);

            // Add body part to message parts.
            $this->composeContent($message);

            // If message is multipart, add attachments as parts.
            if ($message->isMultipart()) {
                $data .= "This is a multi-part message in MIME format.\n";
                $this->composeAttachments($message->getAttachments());

                // Create a string representation of message parts.
                $data .= $this->getComposedParts();
            } else {
                $data .= "\n" .
                        $message->getBody();
            }

            $data .= "\n.";

            if ($this->getResponseCode($this->sendCommand($data)) != self::SMTP_REPLY_OK) {
                throw new Exception('Data block not accepted.');
            }
        } else {
            throw new Exception('Data block failure.');
        }
    }

    /*
     * Commits header information to a string.
     */

    private function getComposedHeaders()
    {
        $headersString = "";
        foreach ($this->headers as $headerName => $headerValue) {
            $headersString .= "{$headerName}: {$headerValue}\n";
        }

        return $headersString;
    }

    /*
     * Adds content body part to message data.
     */

    private function composeContent($message)
    {
        $this->addMessagePart(
                $message->getContentType(), "charset=\"{$message->getCharset()}\"", $this->contentTransferEncoding, null, $message->getBody()
        );
    }

    private function composeAttachments($attachments)
    {
        foreach ($attachments as $name => $content) {
            $this->addMessagePart(
                $content["type"],
                "name=\"{$name}\"",
                "base64",
                "attachment",
                rtrim(chunk_split(base64_encode($content["content"])))
            );
        }
    }

    /*
     * Adds a part to a multipart message.
     */

    private function addMessagePart($contentType, $contentMetadata, $contentTransferEncoding, $contentDisposition, $content)
    {
        $this->messageParts[] = array(
            "type" => $contentType,
            "metadata" => $contentMetadata,
            "encoding" => $contentTransferEncoding,
            "disposition" => $contentDisposition,
            "content" => $content
        );
    }

    private function getComposedParts()
    {
        $partsString = "";
        foreach ($this->messageParts as $part) {
            $partsString .=
                "--{$this->boundary}\n" .
                "Content-Type: {$part["type"]};\n {$part["metadata"]};\n" .
                "Content-Transfer-Encoding: {$part["encoding"]}\n";
            if ($part["disposition"]) {
                $partsString .=
                    "Content-Disposition: {$part["disposition"]};\n";
            }
            $partsString .=
                "\n" .
                "{$part["content"]}\n";
        }

        // End multipart.
        $partsString .= "--{$this->boundary}--";

        return $partsString;
    }

    /*
     * Ends communication with the mail host and closes the socket.
     */

    private function reset()
    {
        if ($this->getResponseCode($this->sendCommand("RSET")) != self::SMTP_REPLY_OK) {
            throw new Exception('Session not reset');
        }
    }

    /*
     * Ends communication with the mail host and closes the socket.
     */

    private function quit()
    {
        if ($this->getResponseCode($this->sendCommand("QUIT")) != self::SMTP_REPLY_CLOSING) {
            throw new Exception('Session not closed');
        }
    }

    private function checkSocket()
    {
        // If no socket is available, open one.
        if (!$this->socket) {
            if (!$this->host) {
                throw new Exception('Host is missing');
            } else {
                $this->socket = new Socket('tcp', $this->host, $this->port, $this->timeout);
            }
        }
    }

    /*
     * Sends the specified data to the socket, and
     * provides its eventual response.
     */

    private function sendCommand($command)
    {
        $this->checkSocket();

        $this->socket->write("{$command}\n");

        return $this->getResponse();
    }

    /*
     * Gets the contents of the socket buffer.
     */

    private function getResponse()
    {
        $this->checkSocket();

        return $this->socket->read(true);
    }

    /*
     * Returns the code part from the host's response.
     */

    private function getResponseCode($response)
    {
        return intval(substr($response, 0, 3));
    }

}
