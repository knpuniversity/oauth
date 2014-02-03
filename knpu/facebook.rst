OAuth with Facebook
===================

Now that Brent has TopCluck up and running he can finally challenge Farmer
Scott to an all out egg counting brawl. Brent knows that if they are both
tracking egg collections on TopCluck Farmer Scott will be proven wrong and
everyone will see how awesome his hens really are.

But what fun is winning if no one else gets to see? So Brent hatches another 
idea: having users share their chicken-laying progress on Facebook. 

Fortunately, Facebook uses OAuth 2.0 for their API, so we're already dangerous.
And like a lot of sites, they even have a PHP library to help us work with
it. Installing it via Composer is easy. In fact, I already added it to our
``composer.json``, so the library is downloaded and ready to go:

.. code-block:: json

    {
        "require": {
            ...
            "facebook/php-sdk": "~3.2.3"
        }
    }

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

    {# views/dashboard.twig #}
    {# ... #}

    <div class="panel panel-default">
        <div class="panel-body">
            Share your progress on Facebook!
            <a href="{{ path('facebook_authorize_start') }}">Connect with Facebook</a>
        </div>
    </div>

When we click this, we hit the code in the first function. Just like before,
our job is to redirect to the authorize URL on Facebook. If we `dig a little`_
bit on Google, we can see this is ``/dialog/oauth``. We could start building
this by hand, but the Facebook SDK can help us out.

If we look at their `simple usage example`_ of the PHP SDK, we can see how to
create the Facebook object. Copy this into the code for our page::

    // src/OAuth2Demo/Client/Controllers/FacebookOAuthController.php
    // ...

    public function redirectToAuthorization()
    {
        $config = array(
          'appId' => 'YOUR_APP_ID',
          'secret' => 'YOUR_APP_SECRET',
          'allowSignedRequest' => false
        );

        $facebook = new \Facebook($config);

        die('Todo: Redirect to Facebook');
    }

We don't need the ``require`` part because we're using Composer, which takes
care of this for us.

Just like with COOP, we need to register our application with Facebook to
get our client id and secret.

Creating your Facebook Application
----------------------------------

Head over to `developers.facebook.com`_ and create a new application. Give
it a name and choose your favorite category. Immediately, we have a App ID
and App Secret. Let's paste these into our code::

    public function redirectToAuthorization()
    {
        $config = array(
          'appId' => '1386038978283XXX',
          'secret' => '9ec32a48f1ad1988e0d4b9e80a17dXXX',
          'allowSignedRequest' => false
        );

        $facebook = new \Facebook($config);

        die('Todo: Redirect to Facebook');
    }

Redirecting the User
--------------------

Now, to get the authorize URL, we can use the `getLoginUrl()`_ function on
the SDK. Remember that this URL always has 3 important things on it: the
client ID, the redirect URI back to our site and the list of scopes we need.
The object already has our client ID, so lets pass the redirect URI and scopes
here. For Facebook, these are called ``redirect_uri`` and ``scope``::

    public function redirectToAuthorization()
    {
        // ...

        $redirectUrl = $this->generateUrl(
            'facebook_authorize_redirect',
            array(),
            true
        );

        $url = $facebook->getLoginUrl(array(
            'redirect_uri' => $redirectUrl,
            'scope' => array('publish_actions', 'email')
        ));

        die('Todo: Redirect to Facebook');
    }

To know which scopes you need, you have to check with the API you're using.
If we google about Facebook API scopes, we `find a page`_ that explains all
of them. We'll ultimately want to be able to get basic user information *and*
post to a user's timeline. These are ``email`` and ``publish_actions``.

Finally, let's redirect the user to this URL. The flow should feel completely
familiar by now::

    public function redirectToAuthorization()
    {
        // ...
        $url = $facebook->getLoginUrl(array(
            'redirect_uri' => $redirectUrl,
            'scope' => array('publish_actions', 'email')
        ));

        return $this->redirect($url);
    }

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

Start by creating a private function that creates the Facebook object, and
use it in both functions::

    public function redirectToAuthorization()
    {
        $facebook = $this->createFacebook();
        // ... the rest of the original function
    }

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();

        die('Todo: Handle after Facebook redirects to us');
    }

    private function createFacebook()
    {
        $config = array(
          'appId' => '1386038978283XXX',
          'secret' => '9ec32a48f1ad1988e0d4b9e80a17dXXX',
          'allowSignedRequest' => false
        );

        return new \Facebook($config);
    }

OAuth tells us that our next step is to make an API request to the token
endpoint to exchange our authorization code for an access token. That's absolutely
right, and it can be done with the help of the SDK::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();

        $userId = $facebook->getUser();
        var_dump($userId);die;

        die('Todo: Handle after Facebook redirects to us');
    }

When we try the process again, we get a valid-looking user id. So, what just
happened?

The ``getUser`` method does a whole lot more than it looks like. It actually
looks for the ``code`` query parameter and makes the API request to get the 
access token automatically! This is awesome, but it's also magic! If you
can keep in mind how OAuth works and what's happening behind the scenes at
each step, you'll be in great shape when something goes wrong.

Handling Failure
----------------

Just like with COOP, we need to handle failure. If we're missing the authorization
code or something else goes wrong behind the scenes, the ``getUser`` method
will return 0. Let's use that to render the error template::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...
        $userId = $facebook->getUser();

        if (!$userId) {
            return $this->render('failed_authorization.twig', array(
                'response' => $request->query->all()
            ));
        }
        // ...
    }

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
authorization. That's nice, but it's unexpected. Just remember that
``getUser`` tries many things: like exchanging the authorization code for
an access token or simply finding an access token that it already stored
in the session.

To see the error page, clear out your session cookie to reset everything.
Log back in, then connect with Facebook but deny the request again. Oh Cluck!
Error page! Without any session data to fall back on, the Facebook object
doesn't have an access token and so can't make an API request to get the user
id.

Saving the Facebook User ID
---------------------------

In CoopOAuthController, once we have the access token, our next step was
to store some details in the database for the user, like the COOP user id,
access token and expiration date.

For Facebook, I want to do something similar, but let's *only* store the
Facebook user id. We can do this without any more work because the ``getUser()``
function gives us that id::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $facebook = $this->createFacebook();
        $userId = $facebook->getUser();
        // ...

        $user = $this->getLoggedInUser();
        $user->facebookUserId = $userId;
        $this->saveUser($user);

        return $this->redirect($this->generateUrl('home'));
    }

And of course, let's redirect back to the homepage after finishing. Try
the whole cycle out - this time approving our application's authorization
request. We now know that a lot is happening behind the scenes.

First, the Facebook object exchanges the authorization code for an access
token and saves it in the session. This all happens when we call ``getUser()``.
Next, we save the Facebook user ID into the database and redirect to the
homepage. Clicking the "User Info" box shows us the Facebook ID.

Store the Access Token in the Database?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So why aren't we storing the access token or expiration? Actually, this is
up to you. The Facebook object is automatically storing the access token
in the session. So, everything is easy right now.

But on the user's next session, the access token will be gone and we'll need
to re-ask the user to authorize. If you want to avoid this, you could store
the Facebook access token in the database. In a second, I'll show you how
you'd use that access token. Of course, these tokens don't last forever, so 
eventually you'll need to re-authorize them or use a :doc:`refresh token <refresh-token>`,
the topic of an upcoming chapter!

.. _`dig a little`: https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/
.. _`simple usage example`: https://developers.facebook.com/docs/php/howto/profilewithgraphapi/
.. _`developers.facebook.com`: https://developers.facebook.com
.. _`getLoginUrl()`: https://developers.facebook.com/docs/reference/php/facebook-getLoginUrl/
.. _`find a page`: https://developers.facebook.com/docs/reference/login/
