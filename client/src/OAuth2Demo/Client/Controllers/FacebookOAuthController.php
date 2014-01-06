<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FacebookOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/facebook/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('facebook_authorize_start');
        $routing->get('/facebook/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('facebook_authorize_redirect');
    }

    public function redirectToAuthorization()
    {
        // generates an absolute URL like http://localhost/facebook/oauth/handle
        // this is the page that the OAuth server will redirect back to
        // see ReceiveAuthorizationCode.php
        $redirectUrl = $this->generateUrl('facebook_authorize_redirect', array(), true);

        $facebook = $this->createFacebook();

        $url = $facebook->getLoginUrl(array(
            'redirect_uri' => $redirectUrl,
            'scope' => array('publish_actions', 'email')
        ));

        return $this->redirect($url);
    }

    /**
     * This is the URL that COOP will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param Application $app
     * @param Request $request
     * @return string|RedirectResponse
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();
        $userId = $facebook->getUser();

        if (!$userId) {
            return $this->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        try {
            $user_profile = $facebook->api('/me');
        } catch (\FacebookApiException $e) {
            return $this->render('failed_token_request.twig', array('response' => $e->getMessage()));
        }

        // are they already logged in?
        $user = $this->getLoggedInUser();
        if (!$user) {
            // hmm, do we have a user with this Facebook ID yet?
            $user = $this->findUserByFacebookId($user_profile['id']);
            $this->loginUser($user);
        }
        if (!$user) {
            // ok, create a new user
            // todo - what if a user with this email already exists?

            $user = $this->createUser(
                $user_profile['email'],
                // a blank password - this user hasn't created a password yet!
                '',
                $user_profile['first_name'],
                $user_profile['last_name']
            );
            $this->loginUser($user);
        }

        $user->facebookUserId = $user_profile['id'];
        $this->saveUser($user);

        // redirect to the homepage!
        return $this->redirect($this->generateUrl('count_eggs'));
    }

    private function createFacebook()
    {
        $config = array(
            'appId' => '471695262942870',
            'secret' => '56f7229148c3478283381484e9c572ea',
            'fileUpload' => false, // optional
            'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
        );

        return new \Facebook($config);
    }
}
