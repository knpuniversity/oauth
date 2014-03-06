<?php

namespace OAuth2Demo\Client\Controllers;

use Guzzle\Http\Client;

class CountEggs extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/count-eggs', array(new self(), 'countEggs'))->bind('count_eggs');
    }

    /**
     * A page that updates the egg count by making an API request to COOP.
     *
     * When it's finished, it just redirects back to the homepage.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function countEggs()
    {
        $user = $this->getLoggedInUser();

        if (!$user->coopAccessToken || !$user->coopUserId) {
            throw new \Exception('Somehow you got here, but without a valid COOP access token! Re-authorize!');
        }

        if ($user->hasCoopAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('coop_authorize_start'));
        }

        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/api/'.$user->coopUserId.'/eggs-count');
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        $response = $request->send();

        if ($response->isError()) {
            throw new \Exception($response->getBody(true));
        }

        $countEggsData = json_decode($response->getBody(), true);

        $eggCount = $countEggsData['data'];
        $this->setTodaysEggCountForUser($this->getLoggedInUser(), $eggCount);

        return $this->redirect($this->generateUrl('home'));
    }
}
