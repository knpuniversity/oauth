<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class CountEggs extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/count-eggs', array(new self(), 'countEggs'))->bind('count_eggs');
    }

    public function countEggs(Application $app)
    {
        // pull the token from the currently-logged-in user
        $token = $this->getLoggedInUser()->coopAccessToken;
        if (!$token) {
            throw new \Exception('Somehow you got here, but without a valid COOP access token! Re-authorize!');
        }

        // make the resource request via http and decode the json response
        $url = $this->getParameter('coop_host').'/api/eggs-count';
        $response = $this->getCurlClient()->post(
            $url,
            // these are the request headers. COOP expects an Authorization header
            array(
                'Authorization' => sprintf('Bearer %s', $token)
            )
        )->send();
        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['error'])) {
            // there is a problem, let's clear out the access token
            $user = $this->getLoggedInUser();
            $user->coopAccessToken = null;
            $this->saveUser($user);

            // todo - handle expiration?
            throw new \Exception($json['error'].' ' .$json['error_description']);
        }

        $eggCount = $json['data'];
        var_dump($json);die;
        $this->setTodaysEggCountForUser($this->getLoggedInUser(), $eggCount);

        return $this->redirect($this->generateUrl('home'));
    }
}
