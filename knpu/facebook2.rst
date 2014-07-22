Facebook: Using the API, Logging in and Failure
===============================================

Sharing on your Wall
--------------------

If the current user has a Facebook ID, let's replace the "Connect
with Facebook" link with one called "Share" that will post to their timeline:

.. code-block:: html+jinja

    {# views/dashboard.twig #}

    <div class="panel-body">
        {% if user.facebookUserId %}
            Share how many eggs you've collected today on Facebook!
            <a href="{{ path('facebook_share_place') }}" class="btn btn-info">Share</a>
        {% else %}
            Share your status on Facebook!
            <a href="{{ path('facebook_authorize_start') }}">Connect with Facebook</a>
        {% endif %}
    </div>

The URL I'm generating here is pointing to a function called ``shareProgressOnFacebook``
in FacebookOAuthController::

    // src/OAuth2Demo/Client/Controllers/FacebookOAuthController.php
    // ...

    public function shareProgressOnFacebook()
    {
        die('Todo: Use Facebook\'s API to post to someone\'s feed');

        return $this->redirect($this->generateUrl('home'));
    }

Click the link to see the message in my ``die`` statement being printed.

Using the Facebook API
~~~~~~~~~~~~~~~~~~~~~~

To post to someone's timeline, we'll use Facebook's API. Like with any API
that uses OAuth, we just need to know the URL, the HTTP method, any data we 
need to send, and how the access token should be attached to the request.

With some `quick googling`_, we see that we need to make a POST request to
``/[USER_ID]/feed`` and send ``message`` and ``access_token`` POST data.

We could *absolutely* do this manually, using the nice Guzzle library from
before. But since we're using the Facebook SDK, it's even easier.

Use the ``createFacebook`` method from before to get our Facebook object
and then use its ``api`` method. This takes 3 arguments: the API URL, the
HTTP method, and any parameters we need to send::

    public function shareProgressOnFacebook()
    {
        $facebook = $this->createFacebook();

        $facebook->api(
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => 'TEST',
            )
        );

        die('Todo: Use Facebook\'s API to post to someone\'s feed');
        // ...
    }

The handy ``$facebook->getUser()`` method gives us the right ``USER_ID`` for
the URL. The only missing piece is the ``access_token`` parameter, which we
can leave out because the Facebook class adds that automatically for us. Again,
that's really cool - just don't lose sight of how things are really working
behind the scenes.

Let's set the return value to a variable and dump it::

    $result = $facebook->api(
        '/'.$facebook->getUser().'/feed',
        'POST',
        array(
            'message' => 'TEST',
        )
    );
    var_dump($result);die;

Refresh the page to try it out. It prints out an array with an ``id`` and
a long number string. The response from ``api`` is specific to what you're
trying to do. In this case, this is the ID of the new post it made. When
I go to my Facebook page, there's my egg-citing post!

Remember that one of the reasons this works is that our authorization URL
included the scope ``publish_actions``. Had we *not* done that, this request
would fail.

.. tip::

    With Facebook and other OAuth servers, users are able to approve *some*
    of the scopes requested by your application but deny others. So code
    defensively - API requests may fail!

Let's make the message more realistic by putting in my egg count and finish
the flow by redirecting back to the homepage::

    public function shareProgressOnFacebook()
    {
        $facebook = $this->createFacebook();
        $eggCount = $this->getTodaysEggCountForUser($this->getLoggedInUser());

        $facebook->api(
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount),
            )
        );

        return $this->redirect($this->generateUrl('home'));
    }

Refresh to try it all again. Check Facebook to see that we're bragging about
our egg-laying hens' progress!

Handling Failure and Re-Authorizing
-----------------------------------

Of course, the API request may fail, especially in the world of OAuth where
the access token might be expired. If any API request fails, the Facebook
class will throw a ``FacebookApiException``. That's great, because
we can wrap the API call in a try-catch block::

    try {
        $facebook->api(
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount),
            )
        );
    } catch (\FacebookApiException $e) {
        // it failed!
    }

If you want to get information about the error, the exception object has
a few useful methods, like ``getResult``, which gives you the raw API error
response or ``getType`` and ``getCode``. Facebook has a helpful page called
`Using the Graph API`_ that talks about the API and also the errors you might
get back. If ``getType`` returns ``OAuthException``, or if the code is
190 or 102, the error is probably related to OAuth and we should try 
re-authorizing them::

    try {
        $facebook->api(
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount),
            )
        );
    } catch (\FacebookApiException $e) {
        // https://developers.facebook.com/docs/graph-api/using-graph-api/#errors
        if ($e->getType() == 'OAuthException' || in_array($e->getCode(), array(190, 102))) {
            // our token is bad - reauthorize to get a new token
            return $this->redirect($this->generateUrl('facebook_authorize_start'));
        }

        // it failed for some odd reason...
        throw $e;
    }

There's even `another page`_ that talks about handling expired tokens in
more detail. If this seems a little unclear, that's probably because Facebook's
error documentation is a little fuzzy.

If it's any other error, I'll just throw the original exception. You could
even render some custom error page.

With any API that uses OAuth, if you can be smart enough to detect when
API requests fail due to an expired access token, you can give your users
a better experience by having them re-authorize your application instead
of just failing.

Re-trying an API Request
~~~~~~~~~~~~~~~~~~~~~~~~

Depending on the error, you might also want to re-try the request. Let's
refactor the API call into a new private method called ``makeApiRequest``::

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

This method does the exact same thing as before. The ``if`` statement checks
to see if ``makeApiRequest`` needs us to redirect the user back to the authorize
URL.

But if we add a new ``$retry`` argument, we could run the request 1 more time if it fails::

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters, $retry = true)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            // ... the check for an expired token

            // re-try one time
            if ($retry) {
                return $this->makeApiRequest($facebook, $url, $method, false);
            }

            // it failed for some odd reason...
            throw $e;
        }
    }

Of course, this is really only interesting if we expect Facebook to have
a decent number of temporary failures. But the big idea is that you should
do your best to figure out *why* a failure has happened and re-try if it
makes sense.

.. note::

    If you're using the `Guzzle`_ library to make API requests (which the
    Facebook class does *not* use), it has built-in support for re-trying
    a request if it fails. See `Guzzle Retry Subscriber`_ (for Guzzle version 4).

This is especially useful in the world of OAuth. We *didn't* store the Facebook
access token in the database. But if we had, we could use it right now and
re-try the request again::

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters, $retry = true)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            if ($e->getType() == 'OAuthException' || in_array($e->getCode(), array(190, 102))) {
                if ($retry) {
                    $user = $this->getLoggedInUser();
                    // this is fake code - we don't have a facebookAccessToken
                    // property in our example project
                    $facebook->setAccessToken($user->facebookAccessToken);

                    return $this->makeApiRequest($facebook, $url, $method, false);
                }

                // ... the same redirect code
            }

            // ... the same throw code
        }
    }

So if the access token were missing from the session and the one in the database
hasn't expired, this will make everything work perfectly smooth. Since this
is fake code, let's remove all the retry code for now::

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            if ($e->getType() == 'OAuthException' || in_array($e->getCode(), array(190, 102))) {
                // our token is bad - reauthorize to get a new token
                return $this->redirect($this->generateUrl('facebook_authorize_start'));
            }

            // it failed for some odd reason...
            throw $e;
        }
    }

Logging in with Facebook
------------------------

Finally, let's make it so the farmers can login with their Facebook account.
Let's start by adding a link on the login page. Just like with "Login with COOP",
the URL is to the page that starts the Facebook authorization process:

.. code-block:: html+jinja

    {# views/user/login.twig #}
    {# ... #}

    <button type="submit" class="btn btn-primary">Login!</button>
    OR
    <div class="btn-group">
        <a href="{{ path('coop_authorize_start') }}" class="btn btn-default">
            Login with COOP
        </a>
        <a href="{{ path('facebook_authorize_start') }}" class="btn btn-default">
            Login with Facebook
        </a>
    </div>

Logging in with Facebook is going to work *exactly* like logging in with
COOP. In fact, let's just copy all the related code from CoopOAuthController
into our FacebookOAuthController::

    // src/OAuth2Demo/Client/Controllers/FacebookOAuthController.php
    // ...

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();
        $userId = $facebook->getUser();
        // ...

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->findOrCreateUser($json);

            $this->loginUser($user);
        }

        $user->facebookUserId = $userId;
        $this->saveUser($user);
        // ...
    }

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByCOOPId($meData['id'])) {
            return $user;
        }

        if ($user = $this->findUserByEmail($meData['email'])) {
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }

But to create a user, we need some basic information, like email, first name
and last name. With COOP, we made an API request to get this information.
Let's do the same thing for Facebook, using the really important endpoint
``/me``. And knowing that things can fail, let's make sure to wrap it in
a try-catch block::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        try {
            $json = $facebook->api('/me');
        } catch (\FacebookApiException $e) {
            return $this->render('failed_token_request.twig', array('response' => $e->getMessage()));
        }
        var_dump($json);die;
        // ...
    }

At this point, we *should* have a valid access token, so if the request fails,
something is very strange. That's why I'm showing an error page instead of
redirecting them to re-authorize.

I'm dumping the result of the API request, so let's logout and try the process. 
But first, reset the database so that it doesn't find our existing user:

.. code-block:: bash

    rm data/topcluck.sqlite

When we login with Facebook, we hit the dump, which holds a lot of nice information
about the user::

.. code-block:: text

    array (size=12)
      'id' => string '100002910877036' (length=15)
      'name' => string '...' (length=17)
      'first_name' => string '...' (length=10)
      'last_name' => string '...' (length=6)
      ...

We're allowed to ask for this information because when we redirect the user
for authorization, we're asking for the ``email`` scope. Let's update the
``findOrCreateUser`` method to use this data.

First, change ``findUserByCOOPId`` to ``findUserByFacebookId``, which is
a shortcut method in my app to find a user by the  ``facebookUserId`` column::

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByFacebookId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }
        // ...
    }

Next, change the ``firstName`` and ``lastName`` keys to match Facebook's
API response::

    private function findOrCreateUser(array $meData)
    {
        // ...

        $user = $this->createUser(
            $meData['email'],
            // a blank password - this user hasn't created a password yet!
            '',
            $meData['first_name'],
            $meData['last_name']
        );

        return $user;
    }

It's that easy! Go back to the login page and try the whole process. When
it finishes, we can click on the "User Info" section to see that we're logged
in as a new user.

And that's it! Since Facebook uses OAuth, working with it is almost exactly
like working with COOP. The biggest differene is that Facebook has a PHP
SDK, which makes life easier, but hides some of the OAuth magic that's happening
behind the scenes. But now that you truly understand things, that's no problem
for you!

.. _`quick googling`: https://developers.facebook.com/docs/reference/api/publishing/
.. _`Using the Graph API`: https://developers.facebook.com/docs/graph-api/using-graph-api
.. _`another page`: https://developers.facebook.com/docs/facebook-login/access-tokens#errors
.. _`Guzzle`: http://guzzle.readthedocs.org/
.. _`Guzzle Retry Subscriber`: https://github.com/guzzle/retry-subscriber
