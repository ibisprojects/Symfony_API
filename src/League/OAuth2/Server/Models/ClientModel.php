<?php

/**
 * OAuth 2.0 Client storage interface
 *
 * @package     php-loep/oauth2-server
 * @author      Manoj Sreekumar
 */

namespace League\OAuth2\Server\Models;

use League\OAuth2\Server\Storage\ClientInterface;

class ClientModel implements ClientInterface {

    protected $dbConn;

    public function __construct($databaseConnection) {
        $this->dbConn = $databaseConnection;
    }

    public function getClient($clientId, $clientSecret = null, $redirectUri = null, $grantType = null) {



        if (!is_null($redirectUri) && !is_null($clientSecret)) {
             
            $sql = "SELECT oauth_clients.id, oauth_clients.secret, oauth_client_endpoints.redirect_uri, oauth_clients.name,
                    oauth_clients.auto_approve FROM oauth_clients LEFT JOIN oauth_client_endpoints 
                    ON oauth_client_endpoints.client_id = oauth_clients.id
                    WHERE oauth_clients.id = :clientId AND oauth_clients.secret = :clientSecret AND
                    oauth_client_endpoints.redirect_uri = :redirectUri";
            $stmt = $this->dbConn->prepare($sql);
            $stmt->bindValue("clientId", $clientId);
            $stmt->bindValue("clientSecret", $clientSecret);
            $stmt->bindValue("redirectUri", $redirectUri);
            $stmt->execute();
            $client=$stmt->fetch();
        }
        if (!is_null($redirectUri) && is_null($clientSecret)) {
             
            $sql = "SELECT oauth_clients.id, oauth_clients.secret, oauth_client_endpoints.redirect_uri, oauth_clients.name, oauth_clients.auto_approve
                     FROM oauth_clients LEFT JOIN oauth_client_endpoints ON oauth_client_endpoints.client_id = oauth_clients.id
                     WHERE oauth_clients.id = :clientId AND oauth_client_endpoints.redirect_uri = :redirectUri";
            $stmt = $this->dbConn->prepare($sql);
            $stmt->bindValue("clientId", $clientId); 
            $stmt->bindValue("redirectUri", $redirectUri);
            $stmt->execute();
            $client=$stmt->fetch();
        }
        if (is_null($redirectUri) && (!is_null($clientSecret))) {
             $sql = "SELECT oauth_clients.id, oauth_clients.secret, oauth_clients.name, oauth_clients.auto_approve FROM oauth_clients 
                      WHERE oauth_clients.id = :clientId AND oauth_clients.secret = :clientSecret";
            $stmt = $this->dbConn->prepare($sql);
            $stmt->bindValue("clientId", $clientId); 
            $stmt->bindValue("clientSecret", $clientSecret);
            $stmt->execute();
            $client=$stmt->fetch();
        }

        if (!$client) {          
            return false;
        }
        //Authorization sucess
        return $client;
    }

}
