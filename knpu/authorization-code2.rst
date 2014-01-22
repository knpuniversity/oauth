Authorization Code: Saving the Token & Handling Failures
========================================================

What if we want to make other API requests on behalf of Brent later? Where
should we store the access token?

Saving the Access Token Somewhere
---------------------------------

Some access tokens last an hour or two, and are well suited for storing in the
session. Others are long-term tokens, for example facebook provides a 60-day token, 
and these make more sense to store in a database. Either way, storing
the token will free us from having to ask the user to authorize again.

In our app, we're going to store it in the database::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...
        $meData = json_decode($response->getBody(), true);

        $user = $this->getLoggedInUser();
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $meData['id'];
        $this->saveUser($user);

        // ...
    }

This code is specific to my app, but the end result is that I've updated
the ``coopAccessToken`` column on the user table for the currently-authenticated
user. I'm also saving the ``coopUserId``, which we'll need since most API
calls have the user's ID in the URI.

Recording the Expires Time
~~~~~~~~~~~~~~~~~~~~~~~~~~

We can also store the time when the token will expire. I'll create a ``DateTime``
object that represents the expiration time. We can check this
later before trying to make API requests. If the token is expired, we'll
need to send the user through the authorization process again::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresIn.' seconds');
        // ...

        $user = $this->getLoggedInUser();
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $meData['id'];
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);

        // ...
    }

Again, the code here is special to my app, but the end result is just to
update a column in the database for the current user. When we try it, it
runs and hits our ``die`` statement. But if you go to the homepage, the
user drop-down shows us that the COOP user id was saved! Eggcellent...

When Authorization Fails
------------------------

But what if the user declines to authorize our app? If this happens, an OAuth server will
redirect the user back to our ``redirect_uri``. If we start from the homepage
again but deny access on COOP, we can see this. But this time, the page explodes
because our request to ``/token`` is *not* returning an access token. In
fact, COOP hasn't included a ``code`` query parameter in the URL on the
redirect.

This is what a canceled authorization looks like: no authorization code.

Unfortunately, we can't just assume that the user authorized our application.
As we've seen when this happens, the ``code`` query parameter will be missing, 
but the OAuth server should include a few extra query parameters explaining what 
went wrong. These are commonly called ``error`` and ``error_description``. Let's 
grab these and pass them into a template I've already prepared::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        if (!$code) {
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            return $this->render('failed_authorization.twig', array(
                'response' => array(
                    'error' => $error,
                    'error_description' => $errorDescription
                )
            ));
        }

        // ...
    }

When we try the flow again, we see a nicer message. You can really do whatever
you want in your application, just make sure you're handling the possibility
that the user will decline your app's request.

These errors should be documented by the OAuth server, but the standard set
includes "temporarily_unavailable", "server_error", and "access_denied".

When Fetching the Access Token Fails
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There's one other spot where things can fail: when requesting out to ``/token``.
What if the response doesn't have an ``access_token`` field? Under normal
circumstances, this really shouldn't happen, but let's render a different
error template in case it does. Don't worry about the variables I'm passing
into the template, I'm just trying to pass enough information so that we
can see what the problem was::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...
        $request = $http->post('/token', null, array(
            // ...
        ));

        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        // if there is no access_token, we have a problem!!!
        if (!isset($responseArr['access_token'])) {
            return $this->render('failed_token_request.twig', array(
                'response' => $responseArr ? $responseArr : $response
            ));
        }
        // ...
    }

Try the whole cycle again, but approve the app this time. It works the first
time of course. But if you refresh, you'll see this error in action. The
code parameter exists, but it's expired. So, the request to ``/token`` fails.

Redirecting after Success
-------------------------

Until now, we've had an ugly ``die`` statement at the bottom of the code
that handles the OAuth redirect. What you'll actually want to do here is
redirect to some other page. Our work is done for now, so we want to help
the user to continue on our site::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        // redirect back to the homepage
        return $this->redirect($this->generateUrl('home'));
    }

In our application, this code simply redirects us to the homepage. And just
like that, we're done! This is the authorization grant type, which has 2
distinct steps to it:

#. First, redirect the user to the OAuth server using its ``/authorize``
   endpoint, your application's ``client_id``, a ``redirect_uri`` and the
   scopes you want permission for. The URL and how the parameters look may
   be different on other OAuth servers, but the idea will be the same.

#. After authorizing our app, the OAuth server redirects back to a URL on
   our site with a ``code`` query parameter. We can use this, along with our
   ``client_id`` and ``client_secret`` to make an API request to the ``/token``
   endpoint. Now, we have an access token.

Let's finally use it to count some eggs!

Couting Eggs
------------

On the homepage, we still have the "Authorize" button. But now that we have
an access token for the user, we really don't need this anymore. The template
that displays this page is at ``views/dashboard.twig``, and I'm already passing
a ``user`` variable here, which is the currently-authenticated user object.
Let's hide the "Authorize" link if the user has a ``coopUserId`` stored in
the database:

.. code-block:: html+jinja

    {# views/dashboard.twig #}
    {# ... #}

    {% if user.coopUserId %}

    {% else %}
        <a class="btn btn-primary btn-lg" href="{{ path('coop_authorize_start') }}">Authorize</a>
    {% endif %}

If we *do* have a ``coopUserId``, let's add a link the user can click that
will count their daily eggs. Don't worry if you're not familiar with the
code here, we're just generating a URL to a new page that I've already setup:

.. code-block:: html+jinja

    {# views/dashboard.twig #}
    {# ... #}

    {% if user.coopUserId %}
        <a class="btn btn-primary btn-lg" href="{{ path('count_eggs') }}">Count Eggs</a>
    {% else %}
        <a class="btn btn-primary btn-lg" href="{{ path('coop_authorize_start') }}">Authorize</a>
    {% endif %}

When we refresh, we see the new link. Clicking it gives us another todo message.
Open up ``src/OAuth2Demo/Client/Controllers/CountEggs.php``, which is the
code behind this new page.

Making the eggs-count API Request
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Start by copying the ``/api/me`` code from ``CoopOAuthController``, and changing
the method from ``get`` to ``post``, since the ``eggs-count`` endpoint requires
POST::

    // src/OAuth2Demo/Client/Controllers/CountEggs.php
    // ...

    class CountEggs extends BaseController
    {
        // ...
        public function countEggs()
        {
            $http = new Client('http://coop.apps.knpuniversity.com', array(
                'request.options' => array(
                    'exceptions' => false,
                )
            ));

            $request = $http->post('/api/me');
            $request->addHeader('Authorization', 'Bearer '.$accessToken);
            $response = $request->send();
            $meData = json_decode($response->getBody(), true);

            die('Implement this in CountEggs::countEggs');

            return $this->redirect($this->generateUrl('home'));
        }
    }

The endpoint we want to hit now is ``/api/USER_ID/eggs-count``. Fortunately,
we've already saved the COOP user id and access token for the currently logged-in
user to the database. Get that data by using our app's ``$this->getLoggedInUser()``
method and update the URL::

    public function countEggs()
    {
        $user = $this->getLoggedInUser();

        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/api/'.$user->coopUserId.'/eggs-count');
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        // ...
    }

I'll add in some debug code so we can see if this is working::

    public function countEggs()
    {
        // ...

        $request = $http->post('/api/'.$user->coopUserId.'/eggs-count');
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        $response = $request->send();
        echo ($response->getBody(true));die;
        // ...
    }

When we refresh, you should see a nice JSON response. Yea, we're counting
eggs! That'll show Farmer Scott! 

Since the purpose of TopCluck is to keep track of how many eggs each
farmer has collected each day, let's save the new count to the database.
Like before, I've already done all the hard work, so that we can focus on
just the OAuth pieces. Just call ``setTodaysEggCountForUser`` and pass it
the current user and the egg count. While we're here, we can remove the ``die``
statement and redirect the user back to the homepage once we're done::

    public function countEggs()
    {
        // ...

        $response = $request->send();
        $countEggsData = json_decode($response->getBody(), true);

        $eggCount = $countEggsData['data'];
        $this->setTodaysEggCountForUser($this->getLoggedInUser(), $eggCount);

        return $this->redirect($this->generateUrl('home'));
    }

When we refresh, we should get redirected back to the homepage. But on the
right, Farmer Brent's egg count isn't going up. Let's go to COOP and
collect a few more eggs manually. Back on FCL, if we count our eggs again,
we get the updated count. Sweet!

All the Things that can Go Wrong
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The "Count Eggs" page we created works great, but we're not handling any
of the things that might go wrong. First, we're hiding its link, but what
if a user somehow ends up on the page without a ``coopUserId`` or ``coopAccessToken``?
Let's code for this case::

    public function countEggs()
    {
        $user = $this->getLoggedInUser();

        if (!$user->coopAccessToken || !$user->coopUserId) {
            throw new \Exception('Somehow you got here, but without a valid COOP access token! Re-authorize!');
        }

        // ...
    }

I'm throwing an exception message, but we could also handle this differently,
like by redirecting the user to the "Authorize" page to start the OAuth flow.

Another thing we can check for is whether or not the token has expired. This
is possible because we stored the expiration data in the database. I've created
an easy helper method to check for this. If this happens, let's redirect
the user to re-authorize, just like if they had clicked the "Authorize" link::

    public function countEggs()
    {
        $user = $this->getLoggedInUser();

        if (!$user->coopAccessToken || !$user->coopUserId) {
            throw new \Exception('Somehow you got here, but without a valid COOP access token! Re-authorize!');
        }

        if ($user->hasCoopAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('coop_authorize_start'));
        }

        // ...
    }

Finally, what if the API request itself fails? A simple way to handle this might look
like this::

    public function countEggs()
    {
        // ...

        $request = $http->post('/api/'.$user->coopUserId.'/eggs-count');
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        $response = $request->send();

        if ($response->isError()) {
            throw new \Exception($response->getBody(true));
        }

        // ...
    }

Of course, you may want to do something more sophisticated. The response could
also have some error information on it, which you can play around with. For OAuth,
this is important because the call *may* have failed because the ``access_token``
expired. What, I thought we just checked for that? Well, in the real world,
there's no guarantee that the token won't expire before its scheduled time.
Plus, the user may have decided to revoke your token -- what a bully. Be aware, 
and handle accordingly. Once again, the OAuth Server should provide information on the
error in the "error" and "error_description" querystring parameters.

You're now dangerous, so lets move on to let our farmers actualy log into
FCL via COOP.
