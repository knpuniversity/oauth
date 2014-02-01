Using Refresh Tokens
====================

Brent has a big problem. A user can already log in and click a link that
uses the COOP API to count the number of eggs collected that day. But that's
manual, and if a farmer forgets, his egg count will show up as zero.

Intead, he wants to write a CRON JOB that automatically counts the eggs
for every user each day. The problem is that each COOP access token expires
after 24 hours. And since we can't redirect and re-authorize the user from
a CRON job, when a token expires, we can't count eggs.

Refresh Tokens
--------------

Fortunately, OAuth comes with an awesome idea called refresh tokens. If you
have a refresh token, you can use it to get a fresh access token. Not all
OAuth servers support refresh tokens. Facebook, for example, allows you to
get long-lived access tokens, with an expiration of 60 days. But those are
really just access tokens, and when they expire, you'll need to send the
user back through the login flow.

Fortunately, COOP *does* support refresh tokens. Open up the ``CoopOAuthController``
where we make the API request to ``/token``. Let's dump this response and
go through the process::

    TODO: Code: Refresh: Dump token response

Ah hah! The response has an ``access_token`` *and* a ``refresh_token``. Let's
store the refresh token to a column on the user so we can re-use it later::

    TODO: Code: Refresh: Save the refresh token

No Refresh Tokens in the Implicit Grant Type
--------------------------------------------

Even if an OAuth server supports refresh tokens, you won't be given one if
you use the implicit flow. To see what I mean, change the ``response_type``
parameter on our COOP authorize URL to ``token`` and add a ``die`` statement
right at the top of the code that handles the redirect::

    TODO: Code: Refresh: Implicit flow no refresh token

When we try the process again, COOP redirects us back with a URL that contains
an access token instead of the authorization code:

.. code-block::

    http://localhost:9000/coop/oauth/handle#
        access_token=eaf215f677bea1562026df05ecca202163a6c69f
        &expires_in=86400
        &token_type=Bearer
        &scope=eggs-count+profile

Since this is hwo the implicit flow works, this no surprise. But notice
that there's no refresh token. That's one major disadvantage of using the
implicit grant type.

Using the Refresh Token
-----------------------

Let's undo that code and change things back to ask for an authorization code.

We can't see it visually, but when we try the whole process, the user record
in the database now has a ``coopRefreshToken`` saved to it.

I've already started the little script for the CRON job, which you can see
at ``data/refresh_tokens.php``. What we want to do here is use the COOP API
to count and save each user's daily eggs.

But first, we need to make sure that everyone has a non-expired access token.
Let's use a method called ``getExpiringTokens`` that I've already prepared.
This queries the database and returns details for all users whose ``coopAccessExpiresAt``
value is today or earlier::

    TODO: Code: Refresh: use getExpiringTokens

Next, let's iterate over each expiring token. To get a refresh token, we'll
make an API request to the very-familiar ``/token`` endpoint. In fact, we
can start by copying the Guzzle API call from ``CoopOAuthController``::

    TODO: Code: Refresh: Copy /token API call

Of course, we don't have a ``$code`` variable, but we *do* have the user's
refresh token. Change ``grant_type`` to be ``refresh_token`` and replace
the ``code`` parameter with ``refresh_token``. We can also remove the ``redirect_uri``,
which isn't needed with this grant type::

    TODO: Code: Refresh: Changing to refresh_token grant type

Let's try out the API call! Tweak the ``getExpiringTokens()`` method temporarily.
We don't actually have any users with expiring tokens, but this change will
return any tokens expiring in the next month, which should be everyone::

    TODO: Code: Refresh: Changing expiration date temporarily

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

    TODO: Code: Refresh: Saving the new data

Let's add a little message so we can see what's going on::

    TODO: Code: Refresh: Debug message

But when we try it now, the script blows up! Since we're still dumping the
raw response, above the exception we can see the message "Invalid refresh token".
The problem is that we already used our refresh token a moment ago. When we
did, the COOP API gave us a new refresh token and invalidated the old one.
Since we didn't save the new refresh token, we're stuck and need to re-authorize
the user.

Go back to the site, log out, and log back in with COOP. This will get new
access and refresh tokens for the user.

When we try the script now, it works. In fact, we can run it over and over
again without any issues. Since we're storing the new refresh token, we can
use it again in the future.

And now that we've refreshed everyone's access tokens, we could loop through
each user and send an API request to count their eggs. The code for that
would look almost exactly like code in the ``CountEggs.php`` file, so we'll
leave that to you.

Nothing lasts Forever
---------------------

Of course, nothing lasts forever, and even the refresh token will eventually
expire. These tokens commonly last for 14-60 days, and afterwards, you have
no choice but to ask the user to re-authorize your application. This means
that unless your OAuth server has some sort of key that lasts forever, our
CRON job will eventually *not* be able to count the eggs for all of our farmers.
We may need to send them an email to re-authorize or be ok that these inactive
users aren't updated anymore.
