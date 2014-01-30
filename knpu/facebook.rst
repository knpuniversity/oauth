OAuth with Facebook
===================

Now that Brent has TopCluck up and running it is time to challenge Farmer
Scott to an all out egg counting brawl. Brent knows that if they are both
tracking egg collections on TopCluck everyone will see how awesome his hens
really are.

But what fun is winning if no one else gets to see? So Brent hatches another 
idea: having users share their chicken-laying progress on Facebook. 

Fortunately, Facebook uses OAuth 2.0 for their API, so we're already dangerous.
And like a lot of sites, they even have a PHP library to help us work with
it. Installing it via Composer is easy. In fact, I already added it to our
``composer.json``, so the library is downloaded and ready to go.

The library has a simple example, but it's easier to see it integrated in
a real application.

The FacebookOAuthController
---------------------------

We're going to integrate with Facebook using the same authorization code
grant type we just used with COOP. So it shouldn't be any surprise that we
need the same 2 pages as before: 1 that redirects to Facebook and 1 that
handles things after Facebook redirects back to us.

In fact, if you open up ``FacebookOAuthController.php``, you'll see that
I've started us with a setup that looks exactly like we had with COOP.

Starting the Redirect
~~~~~~~~~~~~~~~~~~~~~

Let's start by adding a link on the homepage to "Connect with Facebook":

.. code-block:: html+jinja

    TODO: Code: Facebook: Add Connect Link

When we click this, we hit the code in the first function. Just like before,
our job is to redirect to the authorize URL on Facebook. If we `dig a little`_
bit, we can see this is ``/dialog/oauth`` for Facebook. We could start building
this by hand, but the Facebook SDK can help us out.

If we look at their simple usage example, we can see how to create the Facebook
object. Copy this into the code for our page::

    TODO: Code: Facebook: create SDK object

We don't need the ``require`` part because we're using Composer, which takes
care of this for us.

Just like with COOP, we need to register our application with Facebook to
get our client id and secret.

Creating your Facebook Application
----------------------------------

Head over to `developers.facebook.com`_ and create a new application. Give
it a name and choose your favorite category. Immediately, we have a App ID
and App Secret. Let's paste these into our code.

Redirecting the User
--------------------

Now, to get the authorize URL, we can use the `getLoginUrl()`_ function on
the SDK. Remember that this URL always has 3 important things on it: the
client ID, redirect URI back to our site and the list of scopes we need.
The object already has our client ID, so it makes sense that we'll pass
the redirect URI and scopes here::

    TODO: Code: Facebook: Complete getLoginUrl

To know which scopes you need, you have to check with the API you're using.
If we google about Facebook API scopes, we find a page that explains all
of them. We'll ultimately want to be able to get basic user information *and*
post to a user's timeline. These are ``email`` and ``publish_actions``.

Finally, let's redirect the user to this URL. The flow should feel completely
familiar by now::

    TODO: Code: Facebook: Redirect to login url

Registering the Redirect URI
----------------------------

When we try it out, we *do* go to Facebook's ``/dialog/oauth`` with the ``client_id``,
``redirect_uri`` and ``scope`` parameters. But we get an error:

    Given URL is not allowed by the Application configuration.: One or more
    of the given URLs is not allowed by the App's settings. It must match
    the Website URL or Canvas URL, or the domain must be a subdomain of one
    of the App's domains.

It's complaining about the redirect URL we're sending. For added security,
OAuth servers allow, and sometimes require you to configure your redirect
URL in your application. Go back to our application and click Settings and
then "Add Platform". Choose "Website" and then fill in the URL of your site.

.. note::

    Facebook likes to change their interface, so this may look different
    someday soon! But one way or another, you're looking for a way to register
    your redirect URL.

And just like that, when we try it again, it works. Facebook made us do that
so that no other sites can try to use our app id and have Facebook redirect
back to some other domain. COOP's application settings also have this ability,
but it wasn't required, so we skipped it. But, it's always better to fill
this in.

At the authorize URL, Facebook describes the scopes that we're asking for,
including the ability to post. One nice thing about Facebook is that we can
choose to grant this scope, but make any posts show only to us. That's a
great way to test things.

Getting the Acess Token
-----------------------

When we finish, we're redirected back to our second page, which still has
the original todo message. But we have a ``code`` query parameter, and we
know that it can be exchanged for an access token.

Start by creating a private function that creates the Facebook object and
using it in both functions::

    TODO: Code: Facebook: Refactor to createFacebook

OAuth tells us that our next step is to make an API request to the token
endpoint to exchange our authorization code for an access token. That's absolutely
right, and it can be done with the help of the SDK::

    TODO: Code: Facebook: getUser

When we try the process again, we get a valid-looking user id. So, what just
happened?

The ``getUser`` method does a whole lot more than it looks like. It actually
looks for the ``code`` query parameter and makes the API request to the get
the access token automatically! This is awesome, but it's also magic! If you
can keep in mind how OAuth works and what's happening behind the scenes at
each step, you'll be in great shape when something goes wrong.

Handling Failure
----------------

Just like with COOP, we need to handle failure. If we're missing the authorization
code or something else goes wrong behind the scenes, the ``getUser`` method
will return 0. Let's use that to render the error template::

    TODO: Code: Facebook: Handle access token failure

When something *does* go wrong, Facebook will redirect back to us with information
about what went wrong on the standard ``error`` and ``error_description``
query parameters. Because they're following this OAuth standard, we can easily
find error details and even decide what to do next. For example, if the ``error``
is set to ``access_denied``, then it means the user denied our authorization
request. In our app, I'm just passing all of the query parameters into a template
that will display them.

To try this, we first need to go to Facebook and remove the app from our
account. Unlike COOP, most OAuth servers remember if you authorized an app
and don't ask you again.

On TopCluck, click "Connect with Facebook" again but "Cancel" the authorization
request. After the redirect, we see the ``error``, ``error_description`` and
``error_reason`` query parameters. But instead of seeing the error template,
our valid userId is printed out as if it were successful. What just happened?

Our OAuth flow *did* fail. But even still, the Facebook object looks and
finds a valid access token that it stored in the session from the last, successful
authorization we did. That's nice, but it's unexpected. Just remember that
``getUser`` tries many things: like exchanging the authorization code for
an access token or simply finding an access token that it already stored
in the session.

To see the error page, clear out your session cookie to reset everything.
Log back in, then connect with Facebook but deny the request again. Bam!
Error page! Without any session data to fallback to, the Facebook object
doesn't have an access token and so can't make the API request to ``/me``
to get it.

Saving the Facebook User ID
---------------------------

In CoopOAuthController, once we have the access token, our next step was
to store some details in the database for the user, like the COOP user id,
access token and expiration date.

For Facebook, I want to do something similar, but let's *only* store the
Facebook user id. We can do this without any more work because the ``getUser()``
function gives us that id::

    TODO: Code: Facebook: Saving FB user id

And of course, let's redirect back to the homepage after finishing. Try
the whole cycle out - this time approving our application's authorization
request. We now know that a lot is happening behind the scenes.

First, the Facebook object exchanges the authorization code for an access
token and saves it on the session. This all happens when we call ``getUser()``.
Next, we save the Facebook user ID into the database and redirect to the
homepage. Clicking the "User Info" box, shows us that Facebook ID.

Store the Access Token in the Database?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So why aren't we storing the access token or expiration? Actually, this is
up to you. The Facebook object is automatically storing the access token
in the session. So, everything is easy right now.

But on the user's next session, the access token will be gone and we'll need
to re-ask the user to authorize. If you want to avoid this, you could store
the Facebook access token in the database. In a second, I'll show you how
you'd use that access token. Of course, an access token doesn't last forever,
so eventually you'll need to to re-authorize them or use a :doc:`refresh token <refresh-token>`,
the topic of an upcoming chapter!

Sharing on your Wall
--------------------

If the current user has a Facebook ID, let's replace replace the "Connect
with Facebook" link with one called "Share" that will post to their timeline:

.. code-block:: html+jinja

    TODO: Code: 

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
that uses OAuth, we just need to know what URL we need, the HTTP method,
any data we need to send, and how should attach the access token to the
request.

With some quick googling, we see that we need to make a POST request to
``/[USER_ID]/feed`` and send ``message`` and ``access_token`` POST data.

We could *absolutely* do this manually, using the nice Guzzle library from
before. But since we're using the Facebook SDK, it's even easier.

Use the ``createFacebook`` method from before to get our Facebook object
and then use its ``api`` method. This takes 3 arguments: the API URL, the
HTTP method, and any parameters we need to send::

    TODO: Code: Facebook: Initial share API request

The handy ``$facebook->getUser()`` method gives us the right ``USER_ID`` for
the URL. The only missing piece is the ``access_token`` parameter, which we
can leave out because the Facebook class adds that automatically for us. Again,
that's really cool - just don't lose sight of how things are reall working
behind the scenes.

Let's set the return value to a variable and dump it::

    TODO: Code: Facebook: Dump API response

Refresh the page to try it out. It prints out an array with an ``id`` and
a long number string. The response from ``api`` is specific to what you're
trying to do. In this case, this is the ID of the new post it made. When
I go to my Facebook page, there's my post!

Remember that one of the reasons this works is that our authorization URL
included the scope ``publish_actions``. Had we *not* done that, this request
would fail.

.. tip::

    With Facebook and other OAuth servers, users are able to approve *some*
    of the scopes requested by your application but deny others. So code
    defensively - API requests may fail!

Let's make the message more realistic by putting in my egg count and finish
the flow by redirecting back to the homepage::

    TODO: Code: 

Refresh to try it all again. Check Facebook to see that we're bragging about
our egg-laying progress!

Handling Failure and Re-Authorizing
-----------------------------------

Of course, the API request may fail, especially in the world of OAuth where
the access token might be expired. If any API request fails, the Facebook
class will throw a ``FacebookApiException`` exception. That's great, because
we can wrap the API call in a try-catch block::

    TODO: Code: Facebook: try-catch on API call

If you want to get information about the error, you can use either the ``getResult``
or ``getType`` method on the exception object. For example, if ``getType``
is equal to ``TODO``, it means that we don't have an access token or it expired.
In that case, let's actualy redirect the user and re-start the authorization
process::

    TODO: Code: Facebook: redirect to authorize on error

If it's some other error, I'll just throw the original exception. You could
also render some custom error page.

With any API that uses OAuth, if you can be smart enough to detect when
API requests fail due to an expired access token, you can give your users
a better experience by having them re-authorize your application instead
of just failing.

Re-trying an API Request
~~~~~~~~~~~~~~~~~~~~~~~~

Depending on the error, you might also want to re-try the request. Let's
refactor the API call into a new private method::

    public function shareProgressOnFacebook()
    {
        $eggCount = $this->getTodaysEggCountForUser($this->getLoggedInUser());
        $facebook = $this->createFacebook();

        $this->makeApiRequest(
            $facebook,
            '/'.$facebook->getUser().'/feed',
            'POST',
            array(
                'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount),
            )
        );

        return $this->redirect($this->generateUrl('home'));
    }

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            if ($e->getType() == 'access_denied') {
                // our token is bad - reauthorize to get a new token
                return $this->redirect($this->generateUrl('facebook_authorize_start'));
            }

            // it failed for some odd reason...
            throw $e;
        }
    }

So far, this does exactly the same thing as before. But if we add a new ``$retry``
argument, we could run the request 1 more time if it fails::

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters, $retry = true)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            if ($e->getType() == 'access_denied') {
                // our token is bad - reauthorize to get a new token
                return $this->redirect($this->generateUrl('facebook_authorize_start'));
            }

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
    a request if it fails. See `Retrying Requests`_.

This is especially useful in the world of OAuth. We *didn't* store the Facebook
access token in the database. But if we had, we could use it right now and
re-try the request again::

    TODO: Code: [Facebook: Use access token from db

So if the access token were missing from the session and the one in the database
hasn't expired, this will make everything work perfectly smooth. Since this
is fake code, let's remove all the retry code for now::

    private function makeApiRequest(\Facebook $facebook, $url, $method, $parameters)
    {
        try {
            return $facebook->api($url, $method, $parameters);
        } catch (\FacebookApiException $e) {
            if ($e->getType() == 'access_denied') {
                // our token is bad - reauthorize to get a new token
                return $this->redirect($this->generateUrl('facebook_authorize_start'));
            }

            // it failed for some odd reason...
            throw $e;
        }
    }

Logging in with Facebook
------------------------

Finally, let's make it so the farmers can login with the Facebook account.
Let's start by adding a link on the login page. Just like with "Login with COOP",
the URL is to the page that starts the Facebook authorization process:

.. code-block:: html+jinja

    {# views/user/login.twig #}
    {# ... #}

    <button type="submit" class="btn btn-primary">Login!</button>
    OR
    <div class="btn-group">
        <a href="{{ path('coop_authorize_start') }}" class="btn btn-default">Login with COOP</a>
        <a href="{{ path('facebook_authorize_start') }}" class="btn btn-default">Login with Facebook</a>
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
            $user = $this->findOrCreateUser($meData);

            $this->loginUser($user);
        }

        $user->facebookUserId = $userId;
        $this->saveUser($user);
        // ...
    }

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByFacebookId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }

        if ($user = $this->findUserByEmail($meData['email'])) {
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            // blank 
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }

But to create a user, we need some basic information, like email, first name
and last name. With COOP, we had made an API request to get this information.
Let's do the same thing for Facebook, using the really important endpoint
``/me``. And knowing that things can fail, let's make sure to wrap it in
a try-catch block::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ..

        try {
            $json = $facebook->api('/me');
        } catch (\FacebookApiException $e) {
            return $this->render('failed_token_request.twig', array('response' => $e->getMessage()));
        }

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->findOrCreateUser($json);

            $this->loginUser($user);
        }
        // ...
    }

At this point, we *should* have a valid access token, so if the request fails,
something is very strange. That's why I'm showing an error page instead of
redirecting them to re-authorize. If there's a problem, that could cause
an infinite loop of redirecting the user to authorize.

I'm dumping the result of the API request, so let's logout and try out the
process. But first, reset the database so that it doesn't find our existing
user:

.. code-block:: bash

    rm data/topcluck.sqlite

When we login with Facebook, we hit the dump, which holds a lot of nice information
about the user::

.. code-block:: json

    TODO

We're allow to ask for this information because when we redirect the user
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

.. _`dig a little`: https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/
.. _`developers.facebook.com`: https://developers.facebook.com
.. _`getLoginUrl()`: https://developers.facebook.com/docs/reference/php/facebook-getLoginUrl/
