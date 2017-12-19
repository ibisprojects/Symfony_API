<?php

namespace API\Classes;

use League\OAuth2\Server\Grant\GrantScopeValidator;
use League\OAuth2\Server\Models\SessionModel;
use League\OAuth2\Server\Classes\DBConnection as OAuthDBConn;
use SimpleXMLElement;

class CommonFunctions {

    public static function validateToken($accessToken, $grantType, $scopeID, $ownerType = "user") {
        $dbConn = OAuthDBConn::connect();
        if ($dbConn == null) {
            return false;
        }
        $sessionModel = new SessionModel($dbConn);
        $clientDetails = $sessionModel->validateAccessToken($accessToken);
        if (!$clientDetails) {
            return false;
        }
        if ($clientDetails["owner_type"] != $ownerType) {
            return false;
        }
        $clientID = $clientDetails["client_id"];
        $grantScopeValidator = new GrantScopeValidator();
        if (!$grantScopeValidator->validateGrantType($dbConn, $clientID, $grantType)) {
            return false;
        }
        if (!$grantScopeValidator->validateScope($dbConn, $clientID, $scopeID)) {
            return false;
        }
        return $clientDetails;
    }

    public static function array2xml($array, $xml) {

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild("$key");
                CommonFunctions::array2xml($value, $subnode);
            } else {
                $xml->addChild("$key", "$value");
            }
        }
        return $xml->asXML();
    }

}
