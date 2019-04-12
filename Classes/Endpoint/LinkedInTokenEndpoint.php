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
use Flowpack\OAuth2\Client\Provider\LinkedInProvider;
use Flowpack\OAuth2\Client\Utility\LinkedInApiClient;
use Neos\Flow\Annotations as Flow;
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
 * @param $tokenToInspect
 * @return bool
 * @throws OAuth2Exception
 * @throws \Neos\Flow\Http\Client\CurlEngineException
 * @throws \Neos\Flow\Http\Exception
 */
    public function requestValidatedTokenInformation($tokenToInspect, $redirectUri)
    {
//        \Neos\Flow\var_dump([$tokenToInspect]);
//        \Neos\Flow\var_dump($redirectUri);

        $accessToken = $this->requestAuthorizationCodeGrantAccessToken($tokenToInspect, $redirectUri);

//        \Neos\Flow\var_dump([$accessToken]);
//

//        $requestArguments = [
//
//            "input_token"  => $tokenToInspect,
//            "access_token" => $applicationToken["access_token"],
//            "token_type"   => $applicationToken["token_type"]
//        ];
        $requestArguments = [

            'grant_type'=> self::GRANT_TYPE_AUTHORIZATION_CODE,
            'code'   => $tokenToInspect,
            'redirect_uri' => $this->endpointUri,
            'client_id' => $this->clientIdentifier,
            'client_secret' => $this->clientSecret
//            ,'access_token'=>$accessToken['access_token']
        ];

//        $httpBuildQuery = \http_build_query($requestArguments);
//        \Neos\Flow\var_dump($httpBuildQuery);

        $request = Request::create(new Uri('https://api.linkedin.com/v2?' . \http_build_query($accessToken)));


        $request->setHeader("Authorization", "Bearer ".$accessToken["access_token"]);

//        \Neos\Flow\var_dump($request->getHeader('Authorization'));




        $response = $this->requestEngine->sendRequest($request);

//        \Neos\Flow\var_dump($this->requestEngine);
        \Neos\Flow\var_dump($response);


        $responseContent = $response->getBody();

        \Neos\Flow\var_dump($responseContent);
//     \Neos\Flow\var_dump($response->getStatusCode());
//toDo: Bloqued with an errorserviceErrorCode":0,"message":"Resource null does not exist","status":404
        if ($response->getStatusCode() !== 200) {
            throw new OAuth2Exception(sprintf('The response was not of type 200 but gave code and error %d "%s"', $response->getStatusCode(), $responseContent), 1383758360);
        }
        $responseArray = \json_decode($responseContent, true, 16, JSON_BIGINT_AS_STRING);

        \Neos\Flow\var_dump($responseArray);

//        echo '<div style="position: absolute; z-index: 1000; background-color: red; width: 100%; margin: 11.5% 0 0 0 ">
//        <p>You arrived here</p>
//      </div>';
//        \Neos\Flow\var_dump($responseArray["data"]["error"]["code"]);
//        \Neos\Flow\var_dump($responseArray["data"]["error"]["message"]);
        $responseArray['data']['app_id'] = (string)$responseArray['data']['app_id'];

        $responseArray['data']['user_id'] = (string)$responseArray['data']['user_id'];
        $clientIdentifier = (string)$this->clientIdentifier;
//        \Neos\Flow\var_dump($responseArray);
//        \Neos\Flow\var_dump($clientIdentifier);


        if (!$responseArray['data']['is_valid']
            || $responseArray['data']['app_id'] !== $clientIdentifier
        ) {
            $this->securityLogger->log('Requesting validated token information from the Linked In endpoint did not succeed.', LOG_NOTICE, array('response' => \var_export($responseArray, true), 'clientIdentifier' => $clientIdentifier));
            return false;
        } else {
//        \Neos\Flow\var_dump($responseArray['data']);
            return $responseArray['data'];
        }
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
        return $this->requestAccessToken('li_exchange_token', array('li_exchange_token' => $shortLivedToken));
    }
}
