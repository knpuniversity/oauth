Single Signon
=============

Now that it's possible for users to authorize TopCluck to count their COOP
eggs, Brent's on his way to showing farmer Scott just whose eggs rule the
roost.

Feeling fancy, he wants to make life even easier by letting users skip registration
and just login via COOP. Afterall, every farmer who uses the site will already
have a COOP account.

Since we've done all the authorization code work already, adding "Login with
COOP" or "Login with Facebook" buttons is really easy.

Creating New TopCluck Users
---------------------------

Start back in ``CoopOAuthController.php``, where we handled exchanging the
authorization code for the access token. Right now, this code assumes that
the user is already logged in and updates that account with the COOP details::

    TODO: Code: Auth Code: Saving access token and id to db

But instead, let's actively allow anonymous users to go through the authorization
process. And when they do, let's create a *new* user in our database::

    TODO: Code: Login: Create user if not logged in

Some of these functions are specific to my app, but it's simple: if the user
isn't logged in, create and insert a new user record using the data from
the ``/api/me`` endpoint.

Choosing a Password
~~~~~~~~~~~~~~~~~~~

Notice I'm giving the new user a blank password. Does that mean someone could
login as the user by entering a blank password? That would be a huge security
hole!

The probem is that the user isn't choosing a password. In fact, they're
opt'ing to *not* have one and to use their COOP account instead. So one way
or another, it should *not* be possible to login to this account using *any*
password. Normally, my passwords are encoded before being saved, like all
passwords should be. You can't see it here, but when the password is set
to a blank string, I'm skipping the encoding process and actually setting
the ``password`` to be blank. If someone *does* try to login using a blank
password, it'll be encoded first and won't match what's on the database.

As long as you find some way to prevent anyone from logging in as the user
via a password, you're in good shape! You could also have the user choose
a password - I'll mention that later.

Finally, let's log the user into this new account::

    TODO: Code: Login: Authenticate the user

We still need to handle a few edge-cases, but this creates the user, logs
them in, and then still updates them with the coop details.

Adding the Login with COOP Link
-------------------------------

Let's try it out! Log out and then head over to the login page. Here, we'll
add a "Login with COOP" link. The template that renders this page is at ``views/user/login.twig``:

.. code-block:: html+jinja

    TODO: Code: Adding Login with COOP button

The URL for the link is the same as the "Authorize" button on the homepage.
If you're already logged in, we'll just update your account. But if you're
not, we'll log you in. It's that simple!

Let's also completely reset the database, which you can do just by deleting
the ``data/topcluck.sqlite`` file inside the ``client/`` dir.

When we try it out, we're redirected to COOP, sent back to TopCluck, and
are suddenly logged in. If we look at our user details, we can see we're
logged in as Brent, with COOP User ID 2.

Handling Existing Users
-----------------------

There's one big hole in our logic. If I logout and go through the process
again, it blows up! This time, it tries to create a *second* new user for
Brent instead of using the one from before. Let's fix that::

- check if user is logged in and create the user if they are not
- blank password
- what if the coopUserId exists or the email exists?
    -> look up account based on coopUserId
- log the user in
- add the login link


----> set a password later?
----> finish registration?