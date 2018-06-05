<?php

namespace OAuth\AuthorizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
//League OAuth Imports
use League\OAuth2\Server\Util\Request as oAuthRequest;
use League\OAuth2\Server\Authorization as oAuthAuthorization;
use League\OAuth2\Server\Grant\AuthCode as oAuthCode;
use League\OAuth2\Server\Grant\RefreshToken as oAuthRefreshToken;
//League Model Imports
use League\OAuth2\Server\Models\ClientModel;
use League\OAuth2\Server\Models\ScopeModel;
use League\OAuth2\Server\Models\SessionModel;
//Exceptions
use League\OAuth2\Server\Exception\ClientException;
//Database Connections
use League\OAuth2\Server\Classes\DBConnection as OAuthDBConn;
use Classes\DBConnection\DBConnection as CitSciDB;
//CitSci Tables
use Classes\DBTable\TBLPeople;

class AuthController extends Controller {

    public function __construct() {
        $dbConn = OAuthDBConn::connect();
        if ($dbConn == null) {
            echo("Operation Failed");
            die();
        }
        $this->authserver = new oAuthAuthorization(
                new ClientModel($dbConn), new SessionModel($dbConn), new ScopeModel($dbConn)
        );
        $this->authserver->addGrantType(new oAuthCode());
        $this->authserver->addGrantType(new oAuthRefreshToken());
    }

    //Client start point ibis-apis.nrel.colostate.edu/app.php/oAuth/Auth
    public function indexAction() {
        try {
            //Gets all URL parameters  client_id,redirect_uri,scope and validates them
            $params = $this->authserver->getGrantType('authorization_code')->checkAuthoriseParams();

            $session = new Session();

            $session->start();

            $session->set('client_id', $params['client_id']);
            $session->set('client_details', $params['client_details']);
            $session->set('redirect_uri', $params['redirect_uri']);
            $session->set('response_type', $params['response_type']);
            $session->set('scopes', $params['scopes']);

            if (!empty($params['state'])) {
                $session->set('state', $params['state']);
            }

            return $this->redirect($this->generateUrl('o_auth_authorization_signin'));
        } catch (ClientException $e) {
            return $this->render('OAuthAuthorizationBundle:Default:error.html.twig', array('error_message' => "Error Occured: Please check your authorization parameters"));
        }
    }
    //Asks user for credentials and redirects to authorizeAction on success
    public function signinAction(Request $request) {
        $session = new Session();
        $session->start();
        $params = array();
        $params['client_id'] = $session->get('client_id');
        $params['client_details'] = $session->get('client_details');
        $params['redirect_uri'] = $session->get('redirect_uri');
        $params['response_type'] = $session->get('response_type');
        $params['scopes'] = $session->get('scopes');
        $params['state'] = $session->get('state');
        $params['error_message'] = "";
        try {
            foreach ($params as $key => $value) {
                if ($value === null) {
                    throw new Exception('Authorization parameters not found.');
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

                return $this->redirect($this->generateUrl('o_auth_authorization_authorize'));
            }
        } catch (Exception $e) {
            $params['error_message'] = $e->getMessage();
        }
        $params["signin_path"] = 'o_auth_authorization_signin';
		$params["register_path"] = 'o_auth_authorization_register';
        return $this->render('OAuthAuthorizationBundle:Login:signin.html.twig', $params);
    }

	//Asks user for registration credentials
    public function registerAction(Request $request) {
		try {
			$params = array();
			$params['error_message'] = "";

			if ($request->getMethod() == 'POST') {

				try {
					$citSciDB = new CitSciDB();
					$citSciDBconn = $citSciDB->connect();
				} catch (Exception $e) {
					throw new Exception('There is a problem connecting to the CitSci.org database.');
				}

				// Error Checking

				// FirstName
				$firstName = $request->get('firstname');
				if (strlen($firstName)<2) throw new Exception('First name must be at least 2 characters.');

				// LastName
				$lastName = $request->get('lastname');
				if (strlen($lastName)<2) throw new Exception('Last name must be at least 2 characters.');

				// Email
				$email = $request->get('email');
				//$ValidEmail=TBLPeople::GetSetFromEmail($citSciDBconn, $email);
				//if ($ValidEmail !== false) throw new Exception('This email address is already in use.');

				// Username
				$username = $request->get('username');
				if (strlen($username)<3) throw new Exception('Username must be at least 3 characters.');

				// Password
				$password = $request->get('password');
				if (strlen($password)<5) throw new Exception('Password must be at least 5 characters.');
				// Check if password has quotes in it
				$HasSingleQuotes=strpos($password, '\'');
				$HasDoubleQuotes=preg_match('/"/', $password);
				if (($HasSingleQuotes)||($HasDoubleQuotes)) throw new Exception('Password cannot contain quotation marks.');

				// Check if account already exists
				$ValidEmailPassword=TBLPeople::validateUsernamePassword($citSciDBconn, $username, $password);
				if ($ValidEmailPassword !== false) throw new Exception('This account (username, password combination) already exists.');

				$PersonID = TBLPeople::Register($citSciDBconn, $firstName, $lastName, $email, $username, $password);

				if ($PersonID>0) {
					return $this->redirect($this->generateUrl('o_auth_authorization_signin'));
				}
				else {
                    throw new Exception('There was an error in registration.');
                }
			}

		} catch (Exception $e) {
            $params['error_message'] = $e->getMessage();
        }

		$params["signin_path"] = 'o_auth_authorization_signin';

		return $this->render('OAuthAuthorizationBundle:Login:register.html.twig',$params);
    }

    //Issues authorization code for the logged in user.
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
                    throw new Exception('Authorization parameters not found.');
                }
            }
            $params['user_id'] = $session->get('user_id');
            //Checks if user is logged in
            if ($params['user_id'] === null || trim($params['user_id']) == "") {
                return $this->redirect($this->generateUrl('o_auth_authorization_signin'));
            }

            $params['state'] = $session->get('state');

            // Check if the client should be automatically approved
            $autoApprove = ($params['client_details']['auto_approve'] === '1') ? true : false;
            $approve = null;
            $deny = null;
            if ($request->getMethod() == 'POST') {
                $approve = $request->get('approve');
                $deny = $request->get('deny');
            }

            if (!empty($approve) || $autoApprove === true) {
                // Generate an authorization code
                $code = $this->authserver->getGrantType('authorization_code')->newAuthoriseRequest('user', $params['user_id'], $params);

                // Redirect the user back to the client with an authorization code
                $returnArray = array(
                    'code' => $code,
                    'state' => isset($params['state']) ? $params['state'] : ''
                );

                return $this->redirect($params['client_details']['redirect_uri'] . "?" . http_build_query($returnArray));
            }

            if (!empty($deny)) {
                // Redirect the user back to the client with error
                $returnArray = array(
                    'error' => 'access_denied',
                    'error_message' => $this->authserver->getExceptionMessage('access_denied'),
                    'state' => isset($params['state']) ? $params['state'] : ''
                );
                return $this->redirect($params['client_details']['redirect_uri'] . "?" . http_build_query($returnArray));
            }
        } catch (Exception $e) {
            $params['error_message'] = $e->getMessage();
        }
        $params["authorize_path"] = "o_auth_authorization_authorize";
        return $this->render('OAuthAuthorizationBundle:Login:authorize.html.twig', $params);
    }

    //Client start point to get access token and refresh tokens using authorization code. Server side call with client_secret.
    //http://ibis-apis.nrel.colostate.edu/app_dev.php/oAuth/getAccesToken
    public function accessTokenAction(Request $request) {
        try {
            // Tell the auth server to issue an access token
            $response = $this->authserver->issueAccessToken();
        } catch (League\OAuth2\Server\Exception\ClientException $e) {
            // Throw an exception because there was a problem with the client's request
            $response = array(
                'error' => $this->authserver->getExceptionType($e->getCode()),
                'error_description' => $e->getMessage()
            );
        } catch (Exception $e) {
            $response = array(
                'error' => 'undefined_error',
                'error_description' => $e->getMessage()
            );
        }
        $returnJSON = new Response(json_encode($response));
        $returnJSON->headers->set('Content-Type', 'application/json');
        return $returnJSON;
    }
    //Client start point to refresh access token using refresh code. Server side call with client_secret.
    //http://ibis-apis.nrel.colostate.edu/app_dev.php/oAuth/refreshAccesToken
    public function refreshAccessTokenAction(Request $request) {
        $this->authserver->addGrantType(new oAuthRefreshToken());
        try {
            // Tell the auth server to issue an access token
            $response = $this->authserver->issueAccessToken();
        } catch (League\OAuth2\Server\Exception\ClientException $e) {

            // Throw an exception because there was a problem with the client's request
            $response = array(
                'error' => $this->authserver->getExceptionType($e->getCode()),
                'error_description' => $e->getMessage()
            );
        } catch (Exception $e) {
            $response = array(
                'error' => 'undefined_error',
                'error_description' => $e->getMessage()
            );
        }
        $returnJSON = new Response(json_encode($response));
        $returnJSON->headers->set('Content-Type', 'application/json');
        return $returnJSON;
    }
}
