<?php

/**
 * OAuth 2.0 Client storage interface
 *
 * @package     php-loep/oauth2-server
 * @author      Manoj Sreekumar
 */

namespace League\OAuth2\Server\Models;

use League\OAuth2\Server\Storage\SessionInterface;
use DateTime;

class SessionModel implements SessionInterface {

    protected $dbConn;

    public function __construct($databaseConnection) {
        $this->dbConn = $databaseConnection;
    }

    public function associateAccessToken($sessionId, $accessToken, $expireTime) {
        $sql = "INSERT INTO oauth_session_access_tokens (session_id, access_token, access_token_expires)
       VALUE (:sessionId, :accessToken, :accessTokenExpire)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("sessionId", $sessionId);
        $stmt->bindValue("accessToken", $accessToken);
        $stmt->bindValue("accessTokenExpire", $expireTime);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function associateAuthCode($sessionId, $authCode, $expireTime) {
        $sql = "INSERT INTO oauth_session_authcodes (session_id, auth_code, auth_code_expires)
       VALUE (:sessionId, :authCode, :authCodeExpires)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("sessionId", $sessionId);
        $stmt->bindValue("authCode", $authCode);
        $stmt->bindValue("authCodeExpires", $expireTime);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function associateAuthCodeScope($authCodeId, $scopeId) {

        $sql = "INSERT INTO `oauth_session_authcode_scopes` (`oauth_session_authcode_id`, `scope_id`) VALUES
       (:authCodeId, :scopeId)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("authCodeId", $authCodeId);
        $stmt->bindValue("scopeId", $scopeId);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function associateRedirectUri($sessionId, $redirectUri) {

        $sql = "INSERT INTO oauth_session_redirects (session_id, redirect_uri) VALUE (:sessionId, :redirectUri)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("sessionId", $sessionId);
        $stmt->bindValue("redirectUri", $redirectUri);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function associateRefreshToken($accessTokenId, $refreshToken, $expireTime, $clientId) {
        $sql = "INSERT INTO oauth_session_refresh_tokens (session_access_token_id, refresh_token, refresh_token_expires,
       client_id) VALUE (:accessTokenId, :refreshToken, :expireTime, :clientId)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("accessTokenId", $accessTokenId);
        $stmt->bindValue("refreshToken", $refreshToken);
        $stmt->bindValue("expireTime", $expireTime);
        $stmt->bindValue("clientId", $clientId);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function associateScope($accessTokenId, $scopeId) {
        $sql = "INSERT INTO `oauth_session_token_scopes` (`session_access_token_id`, `scope_id`) VALUE (:accessTokenId, :scopeId)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("accessTokenId", $accessTokenId);
        $stmt->bindValue("scopeId", $scopeId);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function createSession($clientId, $ownerType, $ownerId) {
        $sql = "INSERT INTO oauth_sessions (client_id, owner_type,  owner_id)
       VALUE (:clientId, :ownerType, :ownerId)";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("clientId", $clientId);
        $stmt->bindValue("ownerType", $ownerType);
        $stmt->bindValue("ownerId", $ownerId);
        $stmt->execute();
        return $this->dbConn->lastInsertId();
    }

    public function deleteSession($clientId, $ownerType, $ownerId) {
        $sql = "DELETE FROM oauth_sessions WHERE client_id = :clientId AND owner_type = :type AND owner_id = :typeId";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("clientId", $clientId);
        $stmt->bindValue("type", $ownerType);
        $stmt->bindValue("typeId", $ownerId);
        $stmt->execute();
    }

    public function getAccessToken($accessTokenId) {
        $sql = "SELECT * FROM `oauth_session_access_tokens` WHERE `id` = :accessTokenId";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("accessTokenId", $accessTokenId["session_access_token_id"]);
        $stmt->execute();
        $accessToken = $stmt->fetch();
        if (!$accessToken) {
            return array();
        } else {
            return $accessToken;
        }
    }

    public function getAuthCodeScopes($oauthSessionAuthCodeId) {
        $sql = " SELECT scope_id FROM `oauth_session_authcode_scopes` WHERE oauth_session_authcode_id = :authCodeId";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("authCodeId", $oauthSessionAuthCodeId);
        $stmt->execute();
        $scopes = array();
        while ($scope = $stmt->fetch()) {
            array_push($scopes, $scope);
        }
        return $scopes;
    }

    public function getScopes($accessToken) {

        $sql = " SELECT oauth_scopes.* FROM oauth_session_token_scopes JOIN oauth_session_access_tokens
       ON oauth_session_access_tokens.`id` = `oauth_session_token_scopes`.`session_access_token_id`
       JOIN oauth_scopes ON oauth_scopes.id = `oauth_session_token_scopes`.`scope_id`
       WHERE access_token = :accessToken";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("accessToken", $accessToken);
        $stmt->execute();
        $scopes = array();
        while ($scope = $stmt->fetch()) {
            array_push($scopes, $scope);
        }
        return $scopes;
    }

    public function removeAuthCode($sessionId) {
        $sql = "DELETE FROM oauth_session_authcodes WHERE session_id = :sessionId";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("sessionId", $sessionId);
        $stmt->execute();
    }

    public function removeRefreshToken($refreshToken) {
        $sql = "DELETE FROM `oauth_session_refresh_tokens` WHERE refresh_token = :refreshToken";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("refreshToken", $refreshToken);
        $stmt->execute();
    }

    public function validateAccessToken($accessToken) {

        $sql = "SELECT session_id, oauth_sessions.`client_id`, oauth_sessions.`owner_id`, oauth_sessions.`owner_type`
       FROM `oauth_session_access_tokens` JOIN oauth_sessions ON oauth_sessions.`id` = session_id WHERE
       access_token = :accessToken AND access_token_expires >= UNIX_TIMESTAMP(NOW())";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("accessToken", $accessToken);
        $stmt->execute();
        $accessTokenRes = $stmt->fetch();
        if (!$accessTokenRes) {
            return false;
        } else {
            return $accessTokenRes;
        }
    }   
    
    
    public function validateAuthCode($clientId, $redirectUri, $authCode) {

        $sql = " SELECT oauth_sessions.id AS session_id, oauth_session_authcodes.id AS authcode_id FROM oauth_sessions
       JOIN oauth_session_authcodes ON oauth_session_authcodes.`session_id` = oauth_sessions.id
       JOIN oauth_session_redirects ON oauth_session_redirects.`session_id` = oauth_sessions.id WHERE   
       oauth_session_authcodes.`auth_code` = :authCode AND 
       oauth_session_redirects.`redirect_uri` = :redirectUri AND 
       oauth_session_authcodes.`auth_code_expires` >= :time AND 
       oauth_sessions.client_id = :clientId";
       $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("clientId", $clientId);
        $stmt->bindValue("redirectUri", $redirectUri);
        $dateTime = new DateTime();        
        $stmt->bindValue("time", $dateTime->getTimestamp());
       $stmt->bindValue("authCode", $authCode);
        $stmt->execute();
        $authCodeSet = $stmt->fetch();
        if ($authCodeSet) {
            return $authCodeSet;
        } else {
            return false;
        }
    }

    public function validateRefreshToken($refreshToken, $clientId) {

        $sql = " SELECT session_access_token_id FROM `oauth_session_refresh_tokens` WHERE refresh_token = :refreshToken
AND refresh_token_expires >= UNIX_TIMESTAMP(NOW()) AND client_id = :clientId";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("refreshToken", $refreshToken);
        $stmt->bindValue("clientId", $clientId);
        $stmt->execute();
        $refreshTokenResult = $stmt->fetch();
        if (!$refreshTokenResult) {
            return false;
        } else {
            return $refreshTokenResult;
        }
    }

}
