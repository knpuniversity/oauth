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

        /** @var \Guzzle\Http\Client $http */
        // the Guzzle client object, already prepared for us!
        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->get('/api/'.$user->coopUserId.'/eggs-count');
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        $response = $request->send();
        $meData = json_decode($response->getBody(), true);

        die('Implement this in CountEggs::countEggs');

        return $this->redirect($this->generateUrl('home'));
    }
}
