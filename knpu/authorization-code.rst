Authorization Code Grant Type
=============================

Suddenly, Brent is jolted awake at noon to the sound of farmer Scott driving
his eggs to the market and screaming "Haha, Brent! My chickens lay way more
eggs than yours!" But in reality, Brent *knows* that his chickens are way
better egg-making hens than Scott's... but how to prove it?

Then it hits him! The COOP API has an endpoint to see how many eggs have
been collected from a user's farm each day. Brent decides to create a new
website that will use this endpoint to count how many total eggs a COOP user's
farm has collected. He'll call it: Top Cluck! Fantasy Chicken League, or FCL for short.
To call the ``/api/eggs-count`` endpoint on behalf of each user, the site
will use OAuth to collect an access token for every farmer that signs up.

Once again, the question is: how can each user give FCL an access token that
allows it to count eggs on their behalf?

Starting up FCL
---------------

Let's check out the FCL app, which Brent has already started building. It
lives in the  ``client/`` directory of the code download. I'll use the built-in
PHP web server to run this site:

.. code-block:: bash

    cd client/web
    php -S localhost:9000

.. tip::

    Code along with us! Click the Download link on this page to get the starting
    point of the project.

That command starts a built-in PHP webserver, and it'll just sit right there
until we're ready to turn it off. This project also uses Composer, so let's
copy the ``composer.phar`` file we used earlier into this directory and use
install to download some outside libraries the project uses.

.. note::

    If this doesn't work and PHP simply shows you its command-line options,
    check your PHP version. The built-in web server requires PHP 5.4 or higher.

Put the URL to the site into your browser and load it up. Welcome to Top Cluck!
We already have a leaderboard and basic registration. Go ahead and create an
account, which automatically logs us in.

The site is fully-functional, with database tables ready to keep track of
how many eggs each farmer has collected. The only missing piece is OAuth:
getting the access token for each user so that we can make an API request
to count their eggs.

Redirecting to Authorize
------------------------

Before TopCluck can make an API request to COOP to count my eggs, I need
to authorize it. On the homepage, there's already an Authorize link, which
just prints a message right now.

The code behind this URL lives in the ``src/OAuth2Demo/Client/Controllers/CoopOAuthController.php``
file. You don't even need to understand how this works, just know that whatever
we do here, shows up::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    // ...

    public function redirectToAuthorization(Request $request)
    {
        die('Hallo world!');
    }

The first step of the authorization code grant type is to redirect the user
to a specific URL on COOP. From here the user will authorize our app. 
According to `COOP's API Authentication page`_, we need to redirect
the user to ``/authorize`` and send several query parameters.

In our code, let's start building the URL::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    // ...

    public function redirectToAuthorization(Request $request)
    {
        $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => '?',
            'redirect_uri' => '?',
            'scope' => 'eggs-count profile'
        ));

        var_dump($url);die;
    }

The ``response_type`` type is ``code`` because we're using the Authorization
Code flow. The other valid value is ``token``, which is for a grant type
called implicit flow. We'll see that later.

For ``scopes``, we're using ``profile`` and ``eggs-count`` so that once we're
authorized, we can get some profile data about the COOP user and, of course,
count their eggs.

To get a ``client_id``, let's go to COOP and create a new application that
represents TopCluck. The most important thing is to check the 2 boxes for
profile information and collecting the egg count. I'll show you why in a second.

.. tip::

    If there is already an application with the name you want, just choose
    something different and use that as your ``client_id``.

Copy the ``client_id`` into our URL. Great! The last piece is the ``redirect_uri``,
which is a URL on our site that COOP will send the user to after granting
or denying our application access. We're going to do all kinds of important
things once that happens.

Let's set that URL to be ``/coop/oauth/handle``, which is just another page
that's printing a message. The code for this is right inside the same file,
a little further down::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    // ...

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        die('Implement this in CoopOAuthController::receiveAuthorizationCode');
    }

Instead of hardcoding the URL, I'll use the URL generator that's part of
Silex::

    public function redirectToAuthorization(Request $request)
    {
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => 'TopCluck',
            'redirect_uri' => $redirectUrl,
            'scope' => 'eggs-count profile'
        ));
        // ...
    }

However you make your URL, just make sure it's absolute. Ok, we've built our
authorize URL to COOP, let's redirect the user to it::

    public function redirectToAuthorization(Request $request)
    {
        // ...

        return $this->redirect($url);
    }

That ``redirect`` function is special to my app, so your code may differ. As
long as you somehow redirect the user, you're good.

.. tip::

    Since we're using Silex, the ``redirect`` function is actually a shortcut
    I created to create a new ``RedirectResponse`` object.

Authorizing on COOP
-------------------

Let's try it! Go back to the homepage and click the "Authorize" link. This
takes us to our code, which then redirects us to COOP. We're already logged
in, so it gets straight to asking us to authorize the app. Notice that the
scopes that we included in the URL are clearly communicated. Let's authorize
the app. Later, we'll see what happens if you don't.

When we click the authorization button, we're sent back to the ``redirect_uri``
on TopCluck! Nothing has really happened yet. COOP didn't set any cookies
or anything else. But the URL *does* include a ``code`` query parameter.

Exchanging the Authorization Code for an Access Token
-----------------------------------------------------

This query parameter is called the authorization code, and it's unique
to this grant type. It's not an access token, which is really want we want,
but it's the key to getting that. The authorization code is our temporary
proof that the user said that our application can have an access token.

Let's start by copying the code from the ``collect_eggs.php`` script and 
pasting it here. Go ahead and change the ``client_id`` and ``client_secret`` 
to be from the new client or application we created for TopCluck::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    // ...

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/token', null, array(
            'client_id'     => 'TopCluck',
            'client_secret' => '2e2dfd645da38940b1ff694733cc6be6',
            'grant_type'    => 'authorization_code',
        ));

        // make a request to the token url
        $response = $request->send();
        $responseBody = $response->getBody(true);
        var_dump($responseBody);die;
    }

If we look back at the COOP API Authentication docs, we'll see that ``/token``
has 2 other parameters that are used with the authorization grant type: ``code``
and ``redirect_uri``. I'm already retrieving the ``code`` query parameter, so 
let's fill these in. Make sure to also change the ``grant_type`` to 
``authorization_code`` like it describes in the docs. Finally, dump the
``$responseBody`` to see if this request works::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');
        // ...

        $request = $http->post('/token', null, array(
            'client_id'     => 'TopCluck',
            'client_secret' => '2e2dfd645da38940b1ff694733cc6be6',
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->generateUrl('coop_authorize_redirect', array(), true),
        ));

        // ...
    }

The key to this flow is the ``code`` parameter. When COOP receives our request,
it will check that the authorization code is valid. It also knows which user
the code belongs to, so the access token it returns will let us make API requets
on behalf of *that* user.

But what about the ``redirect_uri``? This parameter is absolutely necessary
for the API request to work, but isn't actually used by COOP. It's a security
measure, and it *must* exactly equal the original ``redirect_uri`` that we
used when we redirected the user.

Ok, let's try it! When we refresh, the API actually gives us an error:

.. code-block:: json

    {
        "error": "invalid_grant",
        "error_description": "The authorization code has expired"
    }

The authorization code has a very short lifetime, typically measured in seconds.
We normally exchange it immediately for an access token, so that's ok! Let's
start the whole process from the homepage again.

.. note::

    Usually, an OAuth server will remember that a user already authorized an
    app and immediately redirect the user back to your app. COOP doesn't do this
    only to make things easier to understand.

This time, the API request to ``/token`` returns an ``access_token``. Woot!
Let's also set ``expires_in`` to a variable, which is the number of seconds
until this access token expires::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        $request = $http->post('/token', null, array(
            'client_id'     => 'TopCluck',
            'client_secret' => '2e2dfd645da38940b1ff694733cc6be6',
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->generateUrl('coop_authorize_redirect', array(), true),
        ));

        // make a request to the token url
        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
    }

Using the Access Token
----------------------

Just like in our CRON script, let's use the access token to make an API request!
One of the endpoints is ``/api/me``, which returns information about the user
that is tied to the access token. Let's make a GET request to this endpoint,
setting the access token on the ``Authorization`` header, just like we did
before::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];

        $request = $http->get('/api/me');
        $request->addHeader('Authorization', 'Bearer '.$accessToken);
        $response = $request->send();
        echo ($response->getBody(true));die;
    }

Try it by going back to the homepage and clicking "Authorize". Simply refreshing
the page won't work here, as the authorization code will have already expired.
With any luck, you'll see a JSON response with information about the user:

.. code-block:: json

    {
        id: "2",
        email: "brent@knpuniversity.com",
        firstName: "Brent",
        lastName: "Shaffer"
    }

This works of course because we're sending an access token that is tied to
Brent's account. This also works because when we redirect the user, we're
asking for the ``profile`` scope.

And with that, we've seen the key parts of the authorization code grant type
and how to use an access token in our application. But where should we store
the token and what if the user denies our application access? We'll look
at these next.

.. _`COOP's API Authentication page`: http://coop.apps.knpuniversity.com/api#authentication
