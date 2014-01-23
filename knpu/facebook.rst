OAuth with Facebook
===================

Farmers are going crazy for TopCluck, so Brent hatches another idea: having
users share their chicken-laying progress on Facebook.

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

Creating the Application
------------------------

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

Remember that when something *does* go wrong, Facebook will redirect back
to us with some extra query parameters holding error information.

.. _`dig a little`: https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/
.. _`developers.facebook.com`: https://developers.facebook.com
.. _`getLoginUrl()`: https://developers.facebook.com/docs/reference/php/facebook-getLoginUrl/

- install the library
- look at its docs real quick?
- copy the whole controller, only with 2 methods
- create a Facebook object and setup the redirect
- create a Facebook app
- look up the scopes we need
- get the Facebook objec in the other method
- call getUser()
- handle no User
- request to /me and handle failure
- update logged in user
- redirect home
- add a link to share progress on facebook
- look up endpoint we need
- do API request, with catch
- redirect
- add a Facebook login URL
- add single-sign on logic


- de-authorizing for testing