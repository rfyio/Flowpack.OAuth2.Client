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
    public function requestValidatedTokenInformation($tokenToInspect)
    {
        \Neos\Flow\var_dump([$tokenToInspect]);

        $applicationToken = $this->requestClientCredentialsGrantAccessToken(); #['r_emailaddres', 'r_liteprofile','w_member_social']
//        \Neos\Flow\var_dump([$applicationToken]);
//

        $requestArguments = [

            "input_token"  => $tokenToInspect,
            "access_token" => $applicationToken["access_token"],
            "token_type"   => $applicationToken["token_type"]
        ];


//        \Neos\Flow\var_dump([$requestArguments["input_token"]]);

//        \Neos\Flow\var_dump([$requestArguments["access_token"]]);
//
//        \Neos\Flow\var_dump([$requestArguments["token_type"]]);
//        echo print_r($requestArguments);

//        echo print_r($requestArguments["access_token"]["access_token"]);

//\Neos\Flow\var_dump(\http_build_query($requestArguments));
//        $request = Request::create(new Uri('https://www.linkedin.com/oauth/v2/authorization' . \http_build_query($requestArguments)));
//        $request = Request::create(new Uri('https://www.linkedin.com/uas/oauth2/authorization' . $requestArguments["access_token"]));
        $request = Request::create(new Uri('https://www.linkedin.com/uas/oauth2/authorization' . \http_build_query($requestArguments)));



        \Neos\Flow\var_dump($request);




        $response = $this->requestEngine->sendRequest($request);
        \Neos\Flow\var_dump($this->requestEngine);
        \Neos\Flow\var_dump($response);

        echo print_r($response);

        $responseContent = $response->getBody();

        \Neos\Flow\var_dump($responseContent);
     \Neos\Flow\var_dump($response->getStatusCode());

        if ($response->getStatusCode() !== 200) {
            throw new OAuth2Exception(sprintf('The response was not of type 200 but gave code and error %d "%s"', $response->getStatusCode(), $responseContent), 1383758360);
        }
        $responseArray = \json_decode($responseContent, true, 16, JSON_BIGINT_AS_STRING);

        \Neos\Flow\var_dump($responseArray);

        echo '<div style="position: absolute; z-index: 1000; background-color: red; width: 100%; margin: 11.5% 0 0 0 ">
        <p>You arrived here</p>
      </div>';
        \Neos\Flow\var_dump($responseArray["data"]["error"]["code"]);
        \Neos\Flow\var_dump($responseArray["data"]["error"]["message"]);
        $responseArray['data']['app_id'] = (string)$responseArray['data']['app_id'];

        $responseArray['data']['user_id'] = (string)$responseArray['data']['user_id'];
        $clientIdentifier = (string)$this->clientIdentifier;
        \Neos\Flow\var_dump($responseArray);
        \Neos\Flow\var_dump($clientIdentifier);


        if (!$responseArray['data']['is_valid']
            || $responseArray['data']['app_id'] !== $clientIdentifier
        ) {
            $this->securityLogger->log('Requesting validated token information from the Facebook endpoint did not succeed.', LOG_NOTICE, array('response' => \var_export($responseArray, true), 'clientIdentifier' => $clientIdentifier));
            return false;
        } else {
        \Neos\Flow\var_dump($responseArray['data']);
            return $responseArray['data'];
        }
    }

//    /**
//     * @param $shortLivedToken
//     * @return mixed|string
//     * @throws OAuth2Exception
//     * @throws \Neos\Flow\Http\Client\CurlEngineException
//     * @throws \Neos\Flow\Http\Exception
//     */
//    public function requestLongLivedToken($shortLivedToken)
//    {
//        return $this->requestAccessToken('li_exchange_token', array('li_exchange_token' => $shortLivedToken));
//    }
}
