<?php

namespace OAuth\AuthorizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use League\OAuth2\Server\Exception\ClientException;
//League Model Imports
use League\OAuth2\Server\Models\ClientModel;
use League\OAuth2\Server\Models\ScopeModel;
use League\OAuth2\Server\Models\SessionModel;
//League OAuth Imports
use League\OAuth2\Server\Grant\ClientCredentials;
use League\OAuth2\Server\Authorization as oAuthAuthorization;
//DataBase Connection
use League\OAuth2\Server\Classes\DBConnection as OAuthDBConn;
use OAuth\AuthorizationBundle\Classes\DBConnection\DBConnection as CitSciDB;

class ClientCredentialController extends Controller {

    public function __construct() {
        $dbConn = OAuthDBConn::connect();
        if ($dbConn == null) {
            echo("Operation Failed");
            die();
        }
        $this->authserver = new oAuthAuthorization(
                new ClientModel($dbConn), new SessionModel($dbConn), new ScopeModel($dbConn)
        );
        $this->authserver->addGrantType(new ClientCredentials());
    }

    public function indexAction() {
        try {

            $tokenArray = $this->authserver->getGrantType('client_credentials')->completeFlow();
            // Redirect the user back to the client with an authorization code
            $returnArray = $tokenArray;
            $returnJSON = new Response(json_encode($returnArray));
            $returnJSON->headers->set('Content-Type', 'application/json');
            return $returnJSON; 
        } catch (ClientException $e) {
            return $this->render('OAuthAuthorizationBundle:Default:error.html.twig', array('error_message' => "Error Occured: Please check your authorization parameters"));
        }
    }

}
