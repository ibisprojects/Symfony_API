<?php

/**
 * OAuth 2.0 Client storage interface
 *
 * @package     php-loep/oauth2-server
 * @author      Manoj Sreekumar
 */

namespace League\OAuth2\Server\Models;

use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeModel implements ScopeInterface {

    protected $dbConn;

    public function __construct($databaseConnection) {
        $this->dbConn = $databaseConnection;
    }

    public function getScope($scope, $clientId = null, $grantType = null) {
        //Validate inputs
        //Get Client Repository   
        $sql = " SELECT * FROM oauth_scopes WHERE scope = :scope";
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bindValue("scope", $scope);     
        $stmt->execute();
        $scope = $stmt->fetch();
         if (!$scope) {
            return false;
        }
        return $scope;
    }

}
