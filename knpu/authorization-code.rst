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

What to do with the Access Token
--------------------------------

Just like we did in our ``collect_eggs.php`` script, we can set the access
token to the ``Authorization: Bearer`` header of any API request we make
to COOP.

We'll do that in a second, but first: where should we store the access token?
Typically, you'll either set it on the user's session or store it with their
user record in the database. Both let you get the access token later when
you need it. If you store it in the database, then when the user logs in
next week, the access token *may* still work for them, which saves us from
asking them to authorize again so we can get a new code. It also may have
expired by then, but we'll cover that later.

In our app, we're going to store it in the database::

    TODO: Code: Auth Code: Save access token to the user

This code is specific to my app, but the end result is that I've set a ``coopAccessToken``
column on the currently user...

won't ask a user to authorize an app more than once, but to make things more clear, COOP doesn't


That made sense when we sent the user
to COOP, because COOP needed to know where to send the user back to after
authorization. But here, this is just an API request. 


When we wrote our CRON job, we used a ``/token`` API endpoint to get an access
token. We'll do the same thing here, except we'll also pass the ``code``.
I'll copy the code from 

The goal of any OAuth grant 

Let me tell you quickly how the whole authorization code grant type looks
like. First, our application, TopCluck, will redirect our user to the ``/authorize``
endpoint of COOP. If the user isn't logged into COOP yet, they'll be prompted
to do that first. Once they have, it'll ask the user to Authorize our TopCluck
app to access their profile information and count their eggs.

Assuming they click to authorize, COOP will redirect the user back to some
URL on TopCluck that we decide. This is the ``redirect_uri`` that we include
as a query parameter when we initially sent the user to COOP. When the user
is redirect back to us, COOP will have added a ``code`` query parameter.

For ``client_id``, instead of re-using our application ID from our CRON job,
let's go to COOP and create a new application.


When a user clicks this,
they shoud be redirected to the Coop site and asked to authorize our application.
Once they do, they'll be redirected back to TopCluck where we'll do some magic
to get their access token. This is probably the OAuth grant type you're most
familiar with. It's called Authorization Code.

When we click the link now, we just see a debug message. Time to get to work.

This application is built in Silex, a microframework. If you're not familiar
with it, don't worry. I'll point out anything you need to know along the way.

The code for this page lives in ``src/OAuth2Demo/Client/Controllers/CoopOAuthController.php``,
and is pretty underwhelming::

    todo - die statement at "Use access token from client credentials"

The purpose 

The feature we need
to implement has a user story like this:

Given I am authenticated
When I click "Authorize"
And I authorize the Application on Coop
Then I should be redirected back to TopCluck
And 

- start the app
- register, login
- redirect over to Coop
    -> create a second app
- handle on the way back
    - exchange for auth token
    - update user with details
    - (/me and other stuff is for sign-on later)
- make endpoint to actually collect eggs


- take (or fail to take) some sort of action on behalf of the user?
- add a note about how less scopes in your app is better
- authorization codes *do* expire
- redirect URI on the app
