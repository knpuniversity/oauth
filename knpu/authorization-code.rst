Authorization Code
==================

Suddenly, Brent is jolted awake at noon to the sound of farmer Scott driving
his eggs to the market and screaming "Haha, Brent! My chickens lay way more
eggs than yours!" But in reality, Brent *knows* that his chickens are way
better egg-making hens than Scott's... but how to prove it?

  *show Brent's and his chickens facing off against Scott and his chickens*

Then it hits him! The COOP API has an endpoint to see how many eggs have
been collected from a user's farm each day. Brent decides to create a new
website that will use this endpoint to count how many total eggs a COOP user's
farm has collected. He'll call it: Fantasy Chicken League, or FCL for short.
To call the ``/api/eggs-count`` endpoint on behalf of each user, the site
will use OAuth to collect an access token for every farmer that signs up.

Once again, the question is: how can each user give FCL an access token that
allows it to count the user's eggs on the user's behalf?

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
until we're ready to turn it off.

.. note::

    If this doesn't work and PHP simply shows you its command-line options,
    check your PHP version. The built-in web server requires PHP 5.4 or higher.

Copy the URL into your browser and load it up. Welcome to Top Cluck! We already
have a leaderboard and a basic registration. Go ahead and create an account,
which automatically logs us on.

The site is fully-functional, with database tables ready to keep track of
how many eggs each farmer has collected. The only missing pieces is OAuth:
getting the access token for each user so that we can make an API request
to count their eggs.

Go ahead and register for an account.

Redirecting to Authorize
------------------------

Before TopCluck can make an API request to COOP to count my eggs, I need
to authorize it. On the homepage, there's already and Authorize link, which
just prints a message right now.

The code behind this URL lives in the ``src/OAuth2Demo/Client/Controllers/CoopOAuthController.php``
file. You don't even need to understand how this works, just know that whatever
we do here, shows up::

    TODO-code: Auth Code: Debugging code

The first step of the authorization code grant type is to redirect the user
to a specific URL on COOP where the user will authorize our app. This endpoint
*always* exists in a server that supports the authorization code OAuth grant
type. And accoring to `COOP's API Authentication page`_, we need to redirect
the user to ``/authorize`` and send ``client_id``, ``response_type`` and
``redirect_uri`` as query parameters.

In our code, let's start building the URL::

    TODO-code: Auth Code: Building redirect URL

The ``response_type`` type is ``code`` because we're using the Authorization
Code flow. The other valid value is ``token``, which is for a grant type
called implicit flow. We'll see that in a second.

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

    TODO: Code: Auth Code: Building redirect URL

Instead of hardcoding the URL, I'll use the URL generator that's part of
Silex::

However you make your URL, just make sure it's absolute. Ok, we've got our
full URL, let's redirect the user to it::

    TODO: Code: Auth Code: Redirecting the user

The ``RedirectReponse`` is just the way that our app redirects users. You
may do it differently in your app. As long as you somehow redirect the user,
you're good.

Authorizing on COOP
-------------------

Let's try it! Go back to the homepage and click the "Authorize" link. This
takes us to our code, which then redirects us to COOP. We're already logged
in, so it gets straight to asking us to authorize the app. Notice that the
scopes that we included in the URL are clearly communicated. Let's authorize
the app. Later, we'll see what happens if you don't.

When we click the authorization button, we're sent back to the ``redirect_uri``
on TopCluck! Nothing has really happened yet. TopCluck didn't set a cookie
we're supposed to read or anything else. But the URL *does* include a ``code``
query parameter.

Exchanging the Authorization Code for an Access Token
-----------------------------------------------------

The ``code`` query parameter is called the authorization code, and it's unique
to this grant type. It's not an access token, which is really want we want,
but it's the key to getting that. The authorization code is our temporary
proof that the user said that our application can have an access token that
allows us to make API requests on their behalf.

Let's start by copying the code from the ``collect_eggs.php`` script that
made the request to ``/token`` and pasting it here. Go ahead and change the
``client_id`` and ``client_secret`` to be from the new client or application
we created for TopCluck::

    TODO: Code: Auth Code: Starting Token API request

If we look back at the COOP API Authentication docs, we'll see that ``/token``
has 2 other parameters that are used with the authorization grant type: ``code``
and ``redirect_uri``. I already have some code that gets the ``code`` query
parameter, so let's fill these in. Make sure to also change the ``grant_type``
to ``authorization_code`` like it describes in the docs. Finally, dump the
``$responseBody`` to see if this request works::

    TODO: Code: Auth Code: Adding code and redirect_uri parameters

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

    TODO: Code: Auth Code: Setting Access token variable

Using the Access Token
----------------------

Just like in our CRON script, let's use the access token to make an API request!
One of the endpoints is ``/api/me``, which returns information about the user
that is tied to the access token. Let's make a GET request to this endpoint,
setting the access token on the ``Authorization`` header, just like we did
before::

    TODO: Code: Auth Code: Requesting /api/me

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

Saving the Access Token Somewhere
---------------------------------

What if we want to make other API requests on behalf of Brent later? Where
should we store the access token?

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
