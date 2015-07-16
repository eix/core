<?php

namespace Eix\Core\Responses\Http;

/**
 * Response which instructs the user agent to redirect to the current URL.
 */
class SelfRedirection extends \Eix\Core\Responses\Http\Redirection
{
    public function issue()
    {
        $this->setNextUrl($this->getRequest()->getCurrentUrl());

        parent::issue();
    }

}
