<?php

namespace Eix\Core\Responses\Http;

use Eix\Services\Log\Logger;
use Eix\Core\Responses\Http as HttpResponse;
use Eix\Services\Net\Http as HttpClient;

/**
 * Response which instructs the user agent to redirect.
 */
class Redirection extends HttpResponse
{
    protected $status = HttpClient::STATUS_SEE_OTHER;
    private $nextUrl;

    public function issue()
    {
        if ($this->nextUrl) {
         Logger::get()->debug("Redirecting to {$this->nextUrl}...");
            $this->addHeader('Location', $this->nextUrl);
        } else {
            throw new \InvalidArgumentException('No redirection URL has been set.');
        }

        parent::issue();
    }

    /**
     * Set the URL the user agent should be redirected to as a result of this
     * response's output.
     * @param string $url the next URL.
     */
    public function setNextUrl($url)
    {
        $this->nextUrl = $url;
    }

    /**
     * Adds one or more status messages to the response data. Since this
     * response causes a redirection, the data is stored in the session.
     *
     * @param string $messages
     */
    protected function addStatusMessage($type, $messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $_SESSION['messages']['status'][$type][] = $message;
            }
        } else {
            $_SESSION['messages']['status'][$type][] = $messages;
        }
    }

}
