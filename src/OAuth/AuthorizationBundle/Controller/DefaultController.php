<?php

namespace OAuth\AuthorizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

    public function testAction(Request $request) {
        $authCode = "";
        $accesstoken = "";
        if ($request->getMethod() == 'GET') {
            $authCode = $request->get("code", "");
            $accesstoken = $request->get("access_token", "");
            $refresh_token = $request->get("refresh_token", "");
            $expires_in = $request->get("expires_in", "");
        }

        $response = array(
            'access_token' => $accesstoken,
            'refresh_token' => $refresh_token,
            'expires_in' => $expires_in,
            'code' => $authCode,
        );
        if ($response['access_token'] != "") {
            $returnJSON = new Response(json_encode($response));
            $returnJSON->headers->set('Content-Type', 'application/json');
            return $returnJSON;
        }
        else{
            return new Response();
        }
    }

    public function testAccessTokenAction(Request $request) {
        $params = array();
        return $this->render('OAuthAuthorizationBundle:Default:accessTokenTest.html.twig', $params);
    }

    public function testClientAction(Request $request) {
        $params = array();
        return $this->render('OAuthAuthorizationBundle:Default:clientTest.html.twig', $params);
    }

    public function logoutAction(Request $request) {
        $session = new Session();
        $session->remove('user_id');
        $previousUrl = $request->getRequestUri();
        $grant = $request->get('grant', "");
        $routesArray = array('authorization' => 'o_auth_authorization_signin',
            'implicit' => 'o_auth_implicit_signin');
        if (array_key_exists($grant, $routesArray)) {
            return $this->redirect($this->generateUrl($routesArray[$grant]));
        } else {
            return $this->render('OAuthAuthorizationBundle:Default:error.html.twig', array('error_message' => "Error Occured: Invalid Grant"));
        }
    }

}
