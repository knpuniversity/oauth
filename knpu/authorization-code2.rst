Authorization Code: Saving the Token & Handling Failures 
========================================================

What if we want to make other API requests on behalf of Brent later? Where
should we store the access token?

Saving the Access Token Somewhere
---------------------------------

Typically, you'll either set it on the user's session or store it with their
user record in the database. Both let you get the access token later when
you need it. If you store it in the database, then when the user logs in
next week, the access token *may* still work for them, which saves us from
asking them to authorize again. The token also may have expired by then,
but we'll cover that later.

In our app, we're going to store it in the database::

    TODO: Code: Auth Code: Save access token to the user

This code is specific to my app, but the end result is that I've updated
the ``coopAccessToken`` column on the user table for the currently-authenticated
user. I'm also saving the ``coopUserId``, which we'll need since most API
calls have the user's ID in the URI.

Recording the Expires Time
~~~~~~~~~~~~~~~~~~~~~~~~~~

We can also store the time when the token will expire. We can check this
later before trying to make API requests. If the token is expired, we'll
need to send the user through the authorization process again::

    TODO: Code: Auth Code: Storing expiration

Again, the code here is special to my app, but the end result is just to
update a column in the database for the current user.

When Authorization Fails
------------------------

But what if the user declines to authorize our app? An OAuth server will
*always* redirect back to our ``redirect_uri``. If we start from the homepage
again but deny access on COOP, we can see this. But this time, the page explodes
because our request to ``/token`` is *not* returning an access token. In
fact, there COOP hasn't included a ``code`` query parameter in the URL when
redirecting.

This is what a canceled authorization looks like: no authorization code.

Unfortunately, we can't just assume that the user authorized our application.
If the ``code`` query parameter is missing, let's display a message. I've
already prepared a little template for us that we can just display::

    TODO: Code: Auth Code: Authorization Declined

When we try the flow again, we see a nicer message. You can really do whatever
you want in your application, just make sure you're handling the possibility
that the user will decline your app's request.

If we start from the homepage again but deny access on COOP, our handling
page totally explodes.

There's one other spot where things can fail: when requesting out to ``/token``.
What if the response doesn't have an ``access_token`` field? Under normal
circumstances, this really shouldn't happen, but let's render a different
error template in case it does. Don't worry about the variables I'm passing
into the template, I'm just trying to pass enough information so that we
can see what the problem was::

    TODO: Code: Auth Code: Access Token Request Fails

Try the whole cycle again, but approve the app this time. It works the first
time of course. But if you refresh, you'll see this error in action. The
code parameter exists, but it's expired.  The request to ``/token`` fails.

Redirecting after Success
-------------------------

Until now, we've had an ugly ``die`` statement at the bottom of the code
that handles the OAuth redirect. What you'll actually want to do here is
redirect to some other page. Our work is done for now, so we want to help
the user keep using our site::

    Auth Code: Redirecting after success

In our application, this code simply redirects us to the homepage. And just
like that, we're done! This is the authorization grant type, which has 2
distinct steps to it:

#. First, redirect the user to the OAuth server using its ``/authorize``
   endpoint, your application's ``client_id``, a ``redirect_uri`` and the
   scopes you want permission for. The URL and how the parameters look may
   be different on other OAuth servers, but the idea will be the same.

#. After authorizing our app, the OAuth server redirects back to a URL on
   our site with a ``code`` query parameter. We an use this, along with our
   ``client_id`` and ``client_secret`` to make an API request to the ``/token``
   endpoint. Now, we have an access token.

Let's finally use the access token to count some eggs!

Couting Eggs
------------

On the homepage, we still have the "Authorize" button. But now that we have
an access token for the user, we really don't need this anymore. The template
that displays this page is at ``views/dashboard.twig``, and I'm already passing
a ``user`` variable here, which is the currently-authenticated user object.
Let's hide the "Authorize" link if the user has a ``coopUserId`` stored in
the database:

.. code-block:: html+jinja

    TODO: Code: API: Hiding Authorize button

If we *do* have a ``coopUserId``, let's add a link the user can click that
will count their daily eggs. Don't worry if you're not familiar with the
code here, we're just generating a URL to new page that I've already setup:

.. code-block:: html+jinja

    API: Adding Count Eggs link

When we refresh, we see the new link. Clicking it gives us another todo message.
Open up ``src/OAuth2Demo/Client/Controllers/CountEggs.php``, which is the
code behind this new page.

Making the eggs-count API Request
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Start by copying the ``/api/me`` code from ``CoopOAuthController``, and changing
the method from ``get`` to ``post``, since the ``eggs-count`` endpoint requires
POST::

    TODO: Code: API: Copy /api/me code for count eggs

The endpoint we want to hit now is ``/api/USER_ID/eggs-count``. Fortunately,
we've already saved the COOP user id and access token for the currently logged-in
user to the database. Get that data by using our app's ``$this->getLoggedInUser()``
method and update the URL::

    TODO: Code: API: Fill in USER_ID and access_token

I'll add in some debug code so we can see if this is working::

    TODO: Code: API: Debug code

When we refresh, you should see a nice JSON response. Yea, we're counting
eggs! Since the purpose of TopCluck is to keep track of how many eggs each
farmer has collected each day, let's save the new count to the database.
Like before, I've already done all the hard work, so that we can focus on
just the OAuth pieces. Just call ``setTodaysEggCountForUser`` and pass it
the current user and the egg count. While we're here, we can remove the ``die``
statement and redirect the user back to the homepage once we're done::

    TODO: Code: API: Saving Daily Egg Count

When we refresh, we should get redirected back to the homepage. But on the
right, we can see Brent climbing up the leaderboard. Let's go to COOP and
collect a few more eggs manually. Back on FCL, if we count our eggs again,
we get the updated count. Sweet!

All the Things that can Go Wrong
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The "Count Eggs" page we created works great, but we're not handling any
of the things that might go wrong. First, we're hiding its link, but what
if a user somehow ends up on the page without a ``coopUserId`` or ``coopAccessToken``?
Let's code for this case::

    TODO: Code: API: Check for no access token

I'm throwing an exception message, but we could also handle this differently,
like by redirecting the user to the "Authorize" page to start the OAuth flow.

Another thing we can check for is whether or not the token has expired. This
is possible because we stored the expiration data in the database. I've created
an easy helper method to check for this. If this happens, let's redirect
the user to re-authorize, just like if they had clicked the "Authorize" link::

Finally, what if the API request itself fails? A simple handling might look
like this::

    TODO: Code: API: Check for API call fail

Of course, you may want to do something more sophisticated. The response may
also have some error information on it, which you can handle. For OAuth,
this is important because the call *may* have failed because the ``access_token``
expired. What, I thought we just checked for that? Well, in the real world,
there's no guarantee that the token won't expire before its scheduled time.
Plus, the user may have decided to revoke your token. Be aware, and handle
accordingly.

You're now dangerous, so lets move on to let our farmers actualy log into
FCL via COOP.