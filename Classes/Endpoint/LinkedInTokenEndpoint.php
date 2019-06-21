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

use Neos\Flow\Annotations as Flow;
use Flowpack\OAuth2\Client\Exception as OAuth2Exception;
use Flowpack\OAuth2\Client\Provider\LinkedInProvider;
use Flowpack\OAuth2\Client\Utility\LinkedInApiClient;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\PsrSecurityLoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class LinkedInTokenEndpoint extends AbstractHttpTokenEndpoint implements TokenEndpointInterface
{

    /**
     * @Flow\Inject
     * @var PsrSecurityLoggerInterface
     */
    protected $securityLogger;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @param $tokenToInspect
     * @return bool
     * @throws OAuth2Exception
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    public function requestValidatedTokenInformation($tokenToInspect)
    {
        $requestArguments = [
            'grant_type' => self::GRANT_TYPE_AUTHORIZATION_CODE,
            'code' => $tokenToInspect,
            'redirect_uri' => $this->endpointUri,
            'client_id' => $this->clientIdentifier,
            'client_secret' => $this->clientSecret,
            'access_token' => $tokenToInspect['access_token']
        ];

        $request = Request::create(new Uri('https://api.linkedin.com/v2/me'));

        $request->setHeader('Authorization', 'Bearer ' . $tokenToInspect["access_token"]);

        $response = $this->requestEngine->sendRequest($request);
        $responseContent = $response->getContent();

        if ($response->getStatusCode() !== 200) {
            throw new OAuth2Exception(\sprintf('The response was not of type 200 but gave code and error %d "%s"', $response->getStatusCode(), $responseContent), 1383758360);
        }
        $responseArray = \json_decode($responseContent, true, 16, JSON_BIGINT_AS_STRING);

        return $responseArray;
    }

    /**
     * @param $shortLivedToken
     * @return mixed|string
     * @throws OAuth2Exception
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    public function requestLongLivedToken($shortLivedToken)
    {
        $redirectUri = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.security.authentication.providers.LinkedInOAuth2Provider.providerOptions.redirectionEndpointUri');
        return $this->requestAccessToken('authorization_code', array('code' => $shortLivedToken, 'redirect_uri' => rawurldecode($redirectUri)));
    }
}
