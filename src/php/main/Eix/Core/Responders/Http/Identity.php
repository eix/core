<?php

namespace Eix\Core\Responders\Http;

use Eix\Core\Application;
use Eix\Core\Responders\Http\Page;
use Eix\Core\Responses\Http\Redirection;
use Eix\Core\Users;
use Eix\Services\Identity\Providers\Google as GoogleIdentityProvider;
use Eix\Services\Net\Http\BadRequestException;
use Eix\Services\Net\Http\NotAuthenticatedException;
use Eix\Services\Net\Mail\AuthenticationException;

/**
 * An HTML-based responder that manages users' identities.
 */
class Identity extends Page
{
    protected $section = '.identity';

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    public function httpGetForHtml()
    {
        $response = parent::httpGetForHtml();

        $openIdMode = $this->getRequest()->getParameter('openid_mode');
        if ($openIdMode) {
            switch ($openIdMode) {
                case 'cancel':
                    // Identification by means of OpenID failed.
                    throw new NotAuthenticatedException('OpenID athentication failed.');
                case 'id_res':
                    // The identity provider has validated the user, so it can
                    // be set as current (as long as it's authorised).
                    try {
                        $user = Users::getFromIdentityProvider(
                            new GoogleIdentityProvider
                        );
                        Users::setCurrent($user);
                        // Redirect users to either the URL they originally
                        // requested, or to the index if the former is not
                        // available.
                        $response = new Redirection($this->getRequest());
                        $response->setNextUrl(
                            Application::getCurrent()->popLastLocator() ?: '/'
                        );
                    } catch (NotAuthenticatedException $exception) {
                        // The current OpenID data is not valid. Request again.
                        $response = $this->getRequestIdentificationResponse();
                    }
                    break;
                default:
                    throw new NotAuthenticatedException('Unsupported OpenID mode.');
            }
        } else {
            // No OpenID response is present, so just ask for identity.
            $response = $this->getRequestIdentificationResponse();
        }

        return $response;
    }

    public function httpPostForHtml()
    {
        $response = null;

        $provider = $this->getRequest()->getParameter('provider');
        switch ($provider) {
            case 'google':
                $response = $this->getStartGoogleAuthenticationResponse();
                break;
            default:
                throw new BadRequestException(
                "Identity provider '{$provider}' is not recognised."
                );
        }

        return $response;
    }

    /**
     * Issues the response that asks a user for identification.
     */
    private function getRequestIdentificationResponse()
    {
        $response = parent::httpGetForHtml();
        $this->setPage('identify');

        return $response;
    }

    /**
     * First step in the Google authentication process.
     * @return Redirection
     */
    private function getStartGoogleAuthenticationResponse()
    {
        $response = new Redirection($this->getRequest());

        $currentUser = Users::getCurrent();
        try {
            // Set up the redirection to Google's authentication URL.
            $identityProvider = new GoogleIdentityProvider;
            $response->setNextUrl($identityProvider->getAuthenticationUrl());
        } catch (AuthenticationException $exception) {
            $response->setErrorMessage('Google authentication did not succeed.');
            $response->setNextUrl($this->getRequest()->getCurrentUrl());
        }

        return $response;
    }

}
