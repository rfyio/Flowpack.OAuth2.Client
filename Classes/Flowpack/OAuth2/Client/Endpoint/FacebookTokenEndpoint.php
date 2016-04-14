<?php
namespace Flowpack\OAuth2\Client\Endpoint;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.OAuth2.Client".*
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Flowpack\OAuth2\Client\Exception as OAuth2Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SecurityLoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class FacebookTokenEndpoint extends AbstractHttpTokenEndpoint implements TokenEndpointInterface
{

    /**
     * @Flow\Inject
     * @var SecurityLoggerInterface
     */
    protected $securityLogger;

    /**
     * Inspect the received access token as documented in https://developers.facebook.com/docs/facebook-login/access-tokens/, section Getting Info about Tokens and Debugging
     *
     * @param string $tokenToInspect
     * @return array
     * @throws OAuth2Exception
     */
    public function requestValidatedTokenInformation($tokenToInspect)
    {
        $this->securityLogger->log('NEIN davor FACEBOOK requestValidatedTokenInformation.', LOG_NOTICE, array('$tokenToInspect' => var_export($tokenToInspect, true)));
        $applicationToken = $this->requestClientCredentialsGrantAccessToken();

        $this->securityLogger->log('FACEBOOK requestValidatedTokenInformation.', LOG_NOTICE, array('$tokenToInspect' => var_export($tokenToInspect, true), '$applicationToken' => var_export($applicationToken, true)));
        $this->securityLogger->log('FACEBOOK [\'access_token\'].', LOG_NOTICE, array('$tokenToInspect' => var_export($tokenToInspect['access_token'], true)));


        $this->securityLogger->log('FACEBOOK [\'$applicationToken\'].', LOG_NOTICE, array('$applicationToken' => var_export($applicationToken, true)));

        $requestArguments = array(
            'input_token' => $tokenToInspect['access_token'],
            'access_token' => $applicationToken['access_token']
        );
        $request = Request::create(new Uri('https://graph.facebook.com/debug_token?' . http_build_query($requestArguments)));
        $response = $this->requestEngine->sendRequest($request);
        $responseContent = $response->getContent();
        if ($response->getStatusCode() !== 200) {
            throw new OAuth2Exception(sprintf('The response was not of type 200 but gave code and error %d "%s"', $response->getStatusCode(), $responseContent), 1383758360);
        }

        $responseArray = json_decode($responseContent, true, 16, JSON_BIGINT_AS_STRING);

        $this->securityLogger->log('TATAT $responseArray.', LOG_NOTICE, array('$responseArray' => var_export($responseArray, true)));

        $responseArray['data']['app_id'] = (string)$responseArray['data']['app_id'];
        $responseArray['data']['user_id'] = (string)$responseArray['data']['user_id'];
        $clientIdentifier = (string)$this->clientIdentifier;

        if (!$responseArray['data']['is_valid']
            || $responseArray['data']['app_id'] !== $clientIdentifier
        ) {
            $this->securityLogger->log('Requesting validated token information from the Facebook endpoint did not succeed.', LOG_NOTICE, array('response' => var_export($responseArray, true), 'clientIdentifier' => $clientIdentifier));
            return false;
        } else {
            return $responseArray['data'];
        }
    }

    /**
     * @param $shortLivedToken
     * @return string
     */
    public function requestLongLivedToken($shortLivedToken)
    {
        return $this->requestAccessToken('fb_exchange_token', array('fb_exchange_token' => $shortLivedToken));
    }
}
