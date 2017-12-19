<?php
namespace League\OAuth2\Server\Grant;

class GrantScopeValidator{
    
    
     public function validateGrantType($dbConn, $clientID, $grantType) {
        //validate client_id and grant_type
        $sql = "SELECT `oauth_client_grants`.`id` FROM `oauth_client_grants` JOIN oauth_clients ON oauth_clients.`id` = client_id WHERE
                grant_type = :grantType AND client_id=:clientID";
        $stmt = $dbConn->prepare($sql);
        $stmt->bindValue("grantType", $grantType);
        $stmt->bindValue("clientID", $clientID);
        $stmt->execute();
        $grantRes = $stmt->fetch();
        if (!$grantRes) {
            return FALSE;
        }
        return TRUE;
    }

    public function validateScope($dbConn, $clientID, $scopeID) {
        //validate client_id and grant_type
        $sql = "SELECT `oauth_client_scopes`.`id` FROM `oauth_client_scopes` 
                JOIN oauth_clients ON oauth_clients.`id` = client_id 
                JOIN oauth_scopes ON oauth_scopes.`id` = scope_id 
                WHERE scope_id = :scopeID AND client_id=:clientID";
        $stmt = $dbConn->prepare($sql);
        $stmt->bindValue("scopeID", $scopeID);
        $stmt->bindValue("clientID", $clientID);
        $stmt->execute();
        $scopeRes = $stmt->fetch();
        if (!$scopeRes) {
            return FALSE;
        }
        return TRUE;
    }  
    
}
