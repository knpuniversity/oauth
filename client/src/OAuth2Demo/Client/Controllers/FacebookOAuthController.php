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
        $facebook = $this->createFacebook();

        $redirectUrl = $this->generateUrl('facebook_authorize_redirect', array(), true);

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

        $userId = $facebook->getUser();

        if (!$userId) {
            return $this->render('failed_authorization.twig', array(
                'response' => $request->query->all()
            ));
        }

        try {
            $json = $facebook->api('/me');
        } catch (\FacebookApiException $e) {
            return $this->render('failed_token_request.twig', array('response' => $e->getMessage()));
        }
        var_dump($json);die;

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->findOrCreateUser($json);

            $this->loginUser($user);
        }

        $user->facebookUserId = $userId;
        $this->saveUser($user);

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
        $eggCount = $this->getTodaysEggCountForUser($this->getLoggedInUser());
        $facebook = $this->createFacebook();

        $ret = $this->makeApiRequest(
            $facebook,
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount),
            )
        );

        // if makeApiRequest returns a redirect, do it! The user needs to re-authorize
        if ($ret instanceof RedirectResponse) {
            return $ret;
        }

        return $this->redirect($this->generateUrl('home'));
    }

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            // https://developers.facebook.com/docs/graph-api/using-graph-api/#errors
            if ($e->getType() == 'OAuthException' || in_array($e->getCode(), array(190, 102))) {
                // our token is bad - reauthorize to get a new token
                return $this->redirect($this->generateUrl('facebook_authorize_start'));
            }

            // it failed for some odd reason...
            throw $e;
        }
    }

    private function createFacebook()
    {
        $config = array(
          'appId' => '1386038978283395',
          'secret' => '9ec32a48f1ad1988e0d4b9e80a17d5bc',
          'allowSignedRequest' => false // optional but should be set to false for non-canvas apps
        );

        return new \Facebook($config);
    }

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByCOOPId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }

        if ($user = $this->findUserByEmail($meData['email'])) {
            // we match by email
            // we have to think if we should trust this. Is it possible to
            // register at COOP with someone else's email?
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            // a blank password - this user hasn't created a password yet!
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }
}
