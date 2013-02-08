<?php
/**
 * An HTML-based responder that manages users' identities.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Services\Net\Http\BadRequestException;
use Nohex\Eix\Services\Net\Http\NotAuthenticatedException;
use Nohex\Eix\Core\Responses\Http\Redirection;
use Nohex\Eix\Services\Identity\Providers\Google as GoogleIdentityProvider;
use Nohex\Eix\Core\Users;
use Nohex\Eix\Core\Application;

class Identity extends \Nohex\Eix\Core\Responders\Http\Page
{
    protected $section = '.identity';

    public function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    /*
     * Overrides the standard HTTP response obtention method with one that
    * uses the template ID to set the location of the template.
    */
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

    /*
     * Overrides the standard HTTP response obtention method with one that
    * uses the template ID to set the location of the template.
    */
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
     * @return \Nohex\Eix\Core\Responses\Http\Redirection
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
