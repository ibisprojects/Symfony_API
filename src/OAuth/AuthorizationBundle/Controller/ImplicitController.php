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
use League\OAuth2\Server\Grant\Implicit;
use League\OAuth2\Server\Authorization as oAuthAuthorization;
//DataBase Connection
use League\OAuth2\Server\Classes\DBConnection as OAuthDBConn;
use Classes\DBConnection\DBConnection as CitSciDB;
//CitSci Tables
use Classes\DBTable\TBLPeople;

class ImplicitController extends Controller {

    public function __construct() {
        $dbConn = OAuthDBConn::connect();
        if ($dbConn == null) {
            echo("Operation Failed");
            die();
        }
        $this->authserver = new oAuthAuthorization(
                new ClientModel($dbConn), new SessionModel($dbConn), new ScopeModel($dbConn)
        );
        $this->authserver->addGrantType(new Implicit());
    }

    public function indexAction() {
        try {
            $params = $this->authserver->getGrantType('implicit')->checkAuthoriseParams();
            $session = new Session();

            $session->start();

            $session->set('client_id', $params['client_id']);
            $session->set('client_details', $params['client_details']);
            $session->set('redirect_uri', $params['redirect_uri']);
            $session->set('response_type', $params['response_type']);
            $session->set('scopes', $params['scopes']);
            return $this->redirect($this->generateUrl('o_auth_implicit_signin'));
        } catch (ClientException $e) {
            return $this->render('OAuthAuthorizationBundle:Default:error.html.twig', array('error_message' => "Error Occured: Please check your authorization parameters"));
        }
    }

    public function signinAction(Request $request) {

        $session = new Session();
        $session->start();
        $params = array();
        $params['client_id'] = $session->get('client_id');
        $params['client_details'] = $session->get('client_details');
        $params['redirect_uri'] = $session->get('redirect_uri');
        $params['response_type'] = $session->get('response_type');
        $params['scopes'] = $session->get('scopes');
        $params['error_message'] = "";
        try {
            foreach ($params as $key => $value) {
                if ($value === null) {
                    throw new Exception('Authorization parmeters not found.');
                }
            }

            if ($request->getMethod() == 'POST') {


                // Get username
                $username = $request->get('username');
                if ($username === null || trim($username) === '') {
                    throw new Exception('please enter your username.');
                }

                // Get password
                $password = $request->get('password');
                if ($password === null || trim($password) === '') {
                    throw new Exception('please enter your password.');
                }
                try {
                    $citSciDB = new CitSciDB();
                    $citSciDBconn = $citSciDB->connect();
                } catch (Exception $e) {
                    throw new Exception('CitSci DB not found.');
                }
                $user = TBLPeople::validateUsernamePassword($citSciDBconn, $username, $password);
                if ($user) {
                    // Set the user's ID to a session
                    $session->set('user_id', $username);
                } else {
                    throw new Exception('Invalid username/password');
                }
            }
            $loggedInUser = $session->get('user_id');

            if (isset($loggedInUser) && $loggedInUser != null && trim($loggedInUser) !== "s") {

                return $this->redirect($this->generateUrl('o_auth_implicit_authorize'));
            }
        } catch (Exception $e) {
            $params['error_message'] = $e->getMessage();
        }
        $params["signin_path"] = 'o_auth_implicit_signin';
        return $this->render('OAuthAuthorizationBundle:Login:signin.html.twig', $params);
    }
	
	//Asks user for registration credentials 
    public function registerAction(Request $request) {
		
		return new Response('REGISTER!');
	}

    public function authorizeAction(Request $request) {

        $session = new Session();
        $session->start();
        $params = array();
        $params['client_id'] = $session->get('client_id');
        $params['client_details'] = $session->get('client_details');
        $params['redirect_uri'] = $session->get('redirect_uri');
        $params['response_type'] = $session->get('response_type');
        $params['scopes'] = $session->get('scopes');
        $params['error_message'] = "";
        try {
            foreach ($params as $key => $value) {
                if ($value === null) {
                    throw new Exception('Authorization parmeters not found.');
                }
            }
            $params['user_id'] = $session->get('user_id');

            if ($params['user_id'] === null || trim($params['user_id']) == "") {
                return $this->redirect($this->generateUrl('o_auth_impicit_signin'));
            }

            // Check if the client should be automatically approved
            $autoApprove = ($params['client_details']['auto_approve'] === '1') ? true : false;
            $approve = null;
            $deny = null;
            if ($request->getMethod() == 'POST') {
                $approve = $request->get('approve');
                $deny = $request->get('deny');
            }

            if ($approve !== null || $autoApprove === true) {
                // Generate an authorization code
                $tokenArray = $this->authserver->getGrantType('implicit')->completeFlow($params);

                // Redirect the user back to the client with an authorization code
                $returnArray = $tokenArray;
                return $this->redirect($params['redirect_uri'] . "?" . http_build_query($returnArray));
            }

            if ($deny !== null) {

                // Redirect the user back to the client with error
                $returnArray = array(
                    'error' => 'access_denied',
                    'error_message' => $this->authserver->getExceptionMessage('access_denied'),
                    'state' => isset($params['state']) ? $params['state'] : ''
                );
                $returnArray = $tokenArray;
                return $this->redirect($params['redirect_uri'] . "?" . http_build_query($returnArray));
            }
        } catch (Exception $e) {
            $params['error_message'] = $e->getMessage();
        }
        $params["authorize_path"] = "o_auth_implicit_authorize";
        return $this->render('OAuthAuthorizationBundle:Login:authorize.html.twig', $params);
    }

}
