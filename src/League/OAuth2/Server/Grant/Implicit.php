<?php
/**
 * OAuth 2.0 implicit grant
 *
 * @package     php-loep/oauth2-server
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) 2013 PHP League of Extraordinary Packages
 * @license     http://mit-license.org/
 * @link        http://github.com/php-loep/oauth2-server
 */

namespace League\OAuth2\Server\Grant;

use League\OAuth2\Server\Request;
use League\OAuth2\Server\Authorization;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Util\SecureKey;
use League\OAuth2\Server\Storage\SessionInterface;
use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Classes\DBConnection;
use League\OAuth2\Server\Grant\GrantScopeValidator;
/**
 * Client credentials grant class
 */
class Implicit extends GrantScopeValidator implements GrantTypeInterface {

    use GrantTrait;

    /**
     * Grant identifier
     * @var string
     */
    protected $identifier = 'implicit';

    /**
     * Response type
     * @var string
     */
    protected $responseType = 'token';

    /**
     * AuthServer instance
     * @var AuthServer
     */
    protected $authServer = null;

    /**
     * Access token expires in override
     * @var int
     */
    protected $accessTokenTTL = null;

    /**
     * Complete the client credentials grant
     * @param  null|array $inputParams
     * @return array
     */
    public function completeFlow($authParams = null)
    {
        // Remove any old sessions the user might have
        $this->authServer->getStorage('session')->deleteSession($authParams['client_id'], 'user', $authParams['user_id']);

        // Generate a new access token
        $accessToken = SecureKey::make();

        // Compute expiry time
        $accessTokenExpiresIn = ($this->accessTokenTTL !== null) ? $this->accessTokenTTL : $this->authServer->getAccessTokenTTL();
        $accessTokenExpires = time() + $accessTokenExpiresIn;

        // Create a new session
        $sessionId = $this->authServer->getStorage('session')->createSession($authParams['client_id'], 'user', $authParams['user_id']);

        // Create an access token
        $accessTokenId = $this->authServer->getStorage('session')->associateAccessToken($sessionId, $accessToken, $accessTokenExpires);

        // Associate scopes with the access token
        foreach ($authParams['scopes'] as $scope) {
            $this->authServer->getStorage('session')->associateScope($accessTokenId, $scope['id']);
        }

        $response = array(
            'access_token'  =>  $accessToken,
            'token_type'    =>  'Bearer',
            'expires'       =>  $accessTokenExpires,
            'expires_in'    =>  $accessTokenExpiresIn,
        );

        return $response;
    }
    
    public function checkAuthoriseParams($inputParams = array()) {
        // Auth params
        $authParams = $this->authServer->getParam(array('client_id', 'redirect_uri', 'response_type', 'scope', 'state', 'client_secret'), 'get', $inputParams);

        if (is_null($authParams['client_id'])) {
            throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_request'), 'client_id'), 0);
        }
        $dbConn = DBConnection::connect();
        if (!parent::validateGrantType($dbConn, $authParams['client_id'], $this->identifier)) {
            throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_client_grant'), 'redirect_uri'), 0);
        }       
        if ($this->authServer->stateParamRequired() === true && is_null($authParams['state'])) {
            throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_request'), 'state'), 0);
        }
        // Validate client ID and redirect URI        
        $clientDetails = $this->authServer->getStorage('client')->getClient($authParams['client_id'],$authParams['client_secret']);

        if ($clientDetails === false) {
            throw new Exception\ClientException($this->authServer->getExceptionMessage('invalid_client'), 8);
        }

        $authParams['client_details'] = $clientDetails;
        if (is_null($authParams['response_type'])) {
            throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_request'), 'response_type'), 0);
        }
        // Ensure response type is one that is recognised
        if (!in_array($authParams['response_type'], $this->authServer->getResponseTypes())) {
             print_r("here");
            throw new Exception\ClientException($this->authServer->getExceptionMessage('unsupported_response_type'), 3);
        }
        // Validate scopes
        $scopes = explode($this->authServer->getScopeDelimeter(), $authParams['scope']);

        for ($i = 0; $i < count($scopes); $i++) {
            $scopes[$i] = trim($scopes[$i]);
            if ($scopes[$i] === '')
                unset($scopes[$i]); // Remove any junk scopes
        }

        if ($this->authServer->scopeParamRequired() === true && $this->authServer->getDefaultScope() === null && count($scopes) === 0) {
            throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_request'), 'scope'), 0);
        } elseif (count($scopes) === 0 && $this->authServer->getDefaultScope() !== null) {
            if (is_array($this->authServer->getDefaultScope())) {
                $scopes = $this->authServer->getDefaultScope();
            } else {
                $scopes = array($this->authServer->getDefaultScope());
            }
        }
        
        $authParams['scopes'] = array();

        foreach ($scopes as $scope) {
            $scopeDetails = $this->authServer->getStorage('scope')->getScope($scope, $authParams['client_id'], $this->identifier);

            if ($scopeDetails === false) {
                throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_scope'), $scope), 4);
            }
            if (!parent::validateScope($dbConn, $authParams['client_id'], $scopeDetails["id"])) {
                throw new Exception\ClientException(sprintf($this->authServer->getExceptionMessage('invalid_client_scope'), 'redirect_uri'), 0);
            }
            $authParams['scopes'][] = $scopeDetails;
        }

        return $authParams;
    }

}
