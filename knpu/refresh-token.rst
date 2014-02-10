Using Refresh Tokens
====================

Brent has a big problem. A user can already log in to TopCluck and click a link that
uses the COOP API to count the number of eggs collected that day. But that's
manual, and if a farmer forgets, his egg count will show up as zero.

Intead, he wants to write a CRON JOB that automatically counts the eggs
for every user each day. The problem is that each COOP access token expires
after 24 hours. And since we can't redirect and re-authorize the user from
a CRON job, when a token expires, we can't count eggs.

Refresh Tokens
--------------

Fortunately, OAuth comes with an awesome idea called refresh tokens. If you
have a refresh token, you can use it to get a new access token. Not all
OAuth servers support refresh tokens. Facebook, for example, allows you to
get long-lived access tokens, with an expiration of 60 days. But those are
really just access tokens, and when they expire, you'll need to send the
user back through the login flow.

Why do refresh tokens exist? If an attacker steals an access token, there is
only a short window they can use it before it expires. If an attacker
gains a refresh token, it is useless to them without the client's credentials, 
as you'll see. Having two keys instead of one is a method often used in security 
to make it harder for attackers to compromise a system.

Fortunately, COOP *does* support refresh tokens. Open up the ``CoopOAuthController``
where we make the API request to ``/token``. Let's dump this response and
go through the process::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        $request = $http->post('/token', null, array(
            // ...
        ));

        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        var_dump($responseArr);die;
        // ...
    }

Ah hah! The response has an ``access_token`` *and* a ``refresh_token``. Let's
store the refresh token to a column on the user so we can re-use it later::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        // after the /token request
        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresIn.' seconds');
        $refreshToken = $responseArr['refresh_token'];

        // ...
        $user->coopRefreshToken = $refreshToken;
        $this->saveUser($user);
        // ...
    }

.. note::

    In order to get a refresh token, you *may* need to pass an extra parameter
    (e.g. offline) when redirecting the user to authorize.

No Refresh Tokens in the Implicit Grant Type
--------------------------------------------

Even if an OAuth server supports refresh tokens, you won't be given one if
you use the implicit flow. To see what I mean, change the ``response_type``
parameter on our COOP authorize URL to ``token`` and add a ``die`` statement
right at the top of the code that handles the redirect::

    public function redirectToAuthorization(Request $request)
    {
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
            'response_type' => 'token',
            'client_id' => 'TopCluck',
            'redirect_uri' => $redirectUrl,
            'scope' => 'eggs-count profile'
        ));

        return $this->redirect($url);
    }

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        die;
        // ...
    }

When we try the process again, COOP redirects us back with a URL that contains
an access token instead of the authorization code:

.. code-block:: text

    http://localhost:9000/coop/oauth/handle#
        access_token=eaf215f677bea1562026df05ecca202163a6c69f
        &expires_in=86400
        &token_type=Bearer
        &scope=eggs-count+profile

Since this is how the implicit flow works, this no surprise. But notice
that there's no refresh token. That's one major disadvantage of using the
implicit grant type.

Using the Refresh Token
-----------------------

Let's undo our change and go back to asking for an authorization code.

We can't see it visually, but when we try the whole process, the user record
in the database now has a ``coopRefreshToken`` saved to it.

I've already started the little script for the CRON job, which you can see
at ``data/refresh_tokens.php``. What we want to do here is use the COOP API
to count and save each user's daily eggs.

But first, we need to make sure that everyone has a non-expired access token.
Let's use a method called ``getExpiringTokens`` that I've already prepared.
This queries the database and returns details for all users whose ``coopAccessExpiresAt``
value is today or earlier::

    // data/refresh_tokens.php
    $app = require __DIR__.'/../bootstrap.php';
    use Guzzle\Http\Client;

    // create our http client (Guzzle)
    $http = new Client('http://coop.apps.knpuniversity.com', array(
        'request.options' => array(
            'exceptions' => false,
        )
    ));

    // refresh all tokens expiring today or earlier
    /** @var \OAuth2Demo\Client\Storage\Connection $conn */
    $conn = $app['connection'];

    $expiringTokens = $conn->getExpiringTokens();

.. note::

    In the background, this is just running a query similar to this:

    .. code-block:: text

        SELECT * FROM users WHERE coopAccessExpiresAt < '2014-XX-YY';

Next, let's iterate over each expiring token. To get a refresh token, we'll
make an API request to the very-familiar ``/token`` endpoint. In fact, I'll
start by copying the Guzzle API call from ``CoopOAuthController``::

    // data/refresh_tokens.php
    // ...

    $expiringTokens = $conn->getExpiringTokens();

    foreach ($expiringTokens as $userInfo) {

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
        var_dump($responseBody);die;
        $responseArr = json_decode($responseBody, true);

    }


Of course, we don't have a ``$code`` variable, but we *do* have the user's
refresh token. Change ``grant_type`` to be ``refresh_token`` and replace
the ``code`` parameter with the ``refresh_token``. We can also remove the ``redirect_uri``,
which isn't needed with this grant type::

    $request = $http->post('/token', null, array(
        'client_id'     => 'TopCluck',
        'client_secret' => '2e2dfd645da38940b1ff694733cc6be6',
        'grant_type'    => 'refresh_token',
        'refresh_token' => $userInfo['coopRefreshToken'],
    ));

Let's try out the API call! Tweak the ``getExpiringTokens()`` method temporarily.
We don't actually have any users with expiring tokens, but this change will
return any tokens expiring in the next month, which should be everyone::

    $expiringTokens = $conn->getExpiringTokens(new \DateTime('+1 month'));

    foreach ($expiringTokens as $userInfo) {
        // ...

        $response = $request->send();
        $responseBody = $response->getBody(true);
        var_dump($responseBody);die;
        $responseArr = json_decode($responseBody, true);
    }

Now, try it by executing the script from the command line:

.. code-block:: bash

    $ php data/refresh_token.php

With any luck, we should see a familiar-looking JSON response:

.. code-block:: json

    {
        "access_token": "1729a2fc9e6d6da2d2cb877c5bf3239fd2c57d0d",
        "expires_in": 86400,
        "token_type": "Bearer",
        "scope": "eggs-count profile",
        "refresh_token":"f6ecef2bf0d16d7c13a983616b30d72ca915ab65"
    }

Perfect! Now we just need to update the user with the new ``coopAccessToken``,
``coopExpiresAt`` and ``coopRefreshToken``. Again, we can copy or re-use
some code from ``CoopOAuthController``, since this is the same response
from there. The ``saveNewTokens`` method is a shortcut to update the user
record with this data::

    // data/refresh_tokens.php
    // ...

    foreach ($expiringTokens as $userInfo) {
        // ...

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresIn.' seconds');
        $refreshToken = $responseArr['refresh_token'];

        $conn->saveNewTokens(
            $userInfo['email'],
            $accessToken,
            $expiresAt,
            $refreshToken
        );
    }

.. tip::

    In the background, this is just running an UPDATE query against this
    user to update the access token, expiration and refresh token columns.

Let's add a little message so we can see what's going on::

    $conn->saveNewTokens(
        $userInfo['email'],
        $accessToken,
        $expiresAt,
        $refreshToken
    );
    // ...

    echo sprintf(
        "Refreshing token for user %s: now expires %s\n\n",
        $userInfo['email'],
        $expiresAt->format('Y-m-d H:i:s')
    );

But when we try it now, the script blows up! Since we're still dumping the
raw response, above the exception we can see the message "Invalid refresh token".
The problem is that when we used the refresh token a second ago, the COOP API
gave us a new one and invalidated the old one. We weren't saving it yet, so
now we're stuck and need to re-authorize the user.

.. note::

    An OAuth server may or may not invalidate the refresh token after using
    it - that's totally up to the server.

Go back to the site, log out, and log back in with COOP. This will get a new
refresh token for the user. And since we're saving the new refresh token, in
our script each time, we can run it over and over again without any issues.

And now that we've refreshed everyone's access tokens, we could loop through
each user and send an API request to count their eggs. The code for that
would look almost exactly like code in the ``CountEggs.php`` file, so we'll
leave that to you.

Nothing lasts Forever
---------------------

Of course, nothing lasts forever, and even the refresh token will eventually
expire. These tokens commonly last for 14-60 days, and afterwards, you have
no choice but to ask the user to re-authorize your application.

.. note::

    A refresh token *could* last forever - it's up to the OAuth server. However,
    it's still possible that the user revokes access in the future.

This means that unless your OAuth server has some sort of key that lasts forever,
our CRON job will eventually *not* be able to count the eggs for all of our
farmers. We may need to send them an email to re-authorize or be ok that
these inactive users aren't updated anymore.
