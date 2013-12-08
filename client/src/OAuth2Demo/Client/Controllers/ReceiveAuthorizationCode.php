<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class ReceiveAuthorizationCode extends ActionableController
{
    public function __invoke(Application $app)
    {
        $request   = $app['request']; // the request object
        $session   = $app['session']; // the session (or user) object
        $twig      = $app['twig'];    // used to render twig templates
        $token_url = $app['parameters']['token_url'];

        // the user denied the authorization request
        if (!$code = $request->get('code')) {
            return $twig->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        // verify the "state" parameter matches this user's session (this is like CSRF - very important!!)
        if ($request->get('state') !== $session->getId()) {
            return $twig->render('failed_authorization.twig', array('response' => array('error_description' => 'Your session has expired.  Please try again.')));
        }

        return $twig->render('show_authorization_code.twig', array('code' => $code, 'token_url' => $token_url));
    }
}
