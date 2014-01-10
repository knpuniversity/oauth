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

        $routing->get('/coop/facebook/share', array(new self(), 'shareProgressOnFacebook'))->bind('facebook_share_place');
    }

    /**
     * This page actually redirects to the Facebook authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @return RedirectResponse
     */
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
     * This is the URL that Facebook will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param  Application             $app
     * @param  Request                 $request
     * @return string|RedirectResponse
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();
        // The Facebook SDK magically looks for the "auth" code and exchanges it for an access token
        // this is all happening, but behind the scenes. Here, if authorization
        // was ok, we'll now be able to get the Facebook userId
        $userId = $facebook->getUser();

        if (!$userId) {
            return $this->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        // Attempt an API request. It may fail is the access token is expired
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
        }
        if (!$user) {
            /*
             * There are a few more things you might need to worry about:
             *  1) What if there is already a user with this email address?
             *      This probably means that this person already has a TopCluck
             *      account, but isn't logged in. A nice solution would be
             *      to ask the user if this is true and have them type in
             *      their password to prove it. Then, instead of creating
             *      a new user, we'll update the existing user.
             *
             *  2) What if the facebookUserId already exists? This would mean
             *      that 2 different accounts have authorized the same
             *      Facebook user. That might be because 1 person has 2 TopCluck
             *      accounts. Maybe this is ok, or maybe it's a problem.
             */

            $user = $this->createUser(
                $user_profile['email'],
                // a blank password - this user hasn't created a password yet!
                '',
                $user_profile['first_name'],
                $user_profile['last_name']
            );
        }

        if (!$this->isUserLoggedIn()) {
            $this->loginUser($user);
        }

        $user->facebookUserId = $user_profile['id'];
        $this->saveUser($user);

        // redirect to the homepage!
        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * Posts your current status to your Facebook wall then redirects to
     * the homepage.
     *
     * @return RedirectResponse
     */
    public function shareProgressOnFacebook()
    {
        $facebook = $this->createFacebook();
        $eggCount = $this->getTodaysEggCountForUser($this->getLoggedInUser());

        try {
            $facebook->api('/'.$facebook->getUser().'/feed', 'POST', array(
                'message' => 'Woh! My chickens have laid '.$eggCount.' eggs today!',
            ));
        } catch (\FacebookApiException $e) {
            // maybe the token has expired! No problem, we'll re-auth :)
            return $this->redirect($this->generateUrl('facebook_authorize_start'));
        }

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * Creates the Facebook SDK object, which helps us to make some of the
     * authorization calls in the background and fills in the "access token"
     * details when we want to make requests to its API.
     *
     * @return \Facebook
     */
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
