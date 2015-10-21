User Login with OAuth
=====================

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

Start back in ``CoopOAuthController.php``, where we handled the exchange of the
authorization code for the access token. Right now, this assumes that
the user is already logged in and updates their account with the COOP details::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    // ...
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...
        $meData = json_decode($response->getBody(), true);

        $user = $this->getLoggedInUser();
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $meData['id'];
        $this->saveUser($user);
        // ...
    }

But instead, let's actively allow anonymous users to go through the authorization
process. And when they do, let's create a *new* user in our database::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        $meData = json_decode($response->getBody(), true);

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->createUser(
                $meData['email'],
                // a blank password - this user hasn't created a password yet!
                '',
                $meData['firstName'],
                $meData['lastName']
            );
        }
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $meData['id'];
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);
        // ...
    }

Some of these functions are specific to my app, but it's simple: if the user
isn't logged in, create and insert a new user record using the data from
the ``/api/me`` endpoint.

Choosing a Password
~~~~~~~~~~~~~~~~~~~

Notice I'm giving the new user a blank password. Does that mean someone could
login as the user by entering a blank password? That would be a huge security
hole!

The problem is that the user isn't choosing a password. In fact, they're
opt'ing to *not* have one and to use their COOP account instead. So one way
or another, it should *not* be possible to login to this account using *any*
password. Normally, my passwords are encoded before being saved, like all
passwords should be. You can't see it here, but when the password is set
to a blank string, I'm skipping the encoding process and actually setting
the ``password`` in the database to be blank. If someone *does* try to login 
using a blank password, it'll be encoded first and won't match what's in the database.

As long as you find some way to prevent anyone from logging in as the user
via a password, you're in good shape! You could also have the user choose
a password right now or have an area to do that in their profile. I'll mention
the first approach in a second.

Finally, let's log the user into this new account::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->createUser(
                $meData['email'],
                // a blank password - this user hasn't created a password yet!
                '',
                $meData['firstName'],
                $meData['lastName']
            );

            $this->loginUser($user);
        }

        // ...
    }

We still need to handle a few edge-cases, but this creates the user, logs
them in, and then still updates them with the COOP details.

Adding the Login with COOP Link
-------------------------------

Let's try it out! Log out and then head over to the login page. Here, we'll
add a "Login with COOP" link. The template that renders this page is at ``views/user/login.twig``:

.. code-block:: html+jinja

    {# views/user/login.twig #}

    <div class="form-group">
        <div class="col-lg-10 col-lg-offset-2">
            <button type="submit" class="btn btn-primary">Login!</button>
            OR
            <a href="{{ path('coop_authorize_start') }}"
                class="btn btn-default">Login with COOP</a>
        </div>
    </div>

The URL for the link is the same as the "Authorize" button on the homepage.
If you're already logged in, we'll just update your account. But if you're
not, we'll create a new account and log you in. It's that simple!

Let's also completely reset the database, which you can do just by deleting
the ``data/topcluck.sqlite`` file inside the ``client/`` directory:

.. code-block:: bash

    $ rm data/topcluck.sqlite

When we try it out, we're redirected to COOP, sent back to TopCluck, and
are suddenly logged in. If we look at our user details, we can see we're
logged in as Brent, with COOP User ID 2.

Handling Existing Users
-----------------------

There's one big hole in our logic. If I logout and go through the process
again, it blows up! This time, it tries to create a *second* new user for
Brent instead of using the one from before. Let's fix that. For organization,
I'm going to create a new private function called ``findOrCreateUser`` in
this same class. If we can find a user with this COOP User ID, then we can
just log the user into that account. If not, we'll keep creating a new one::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // ...

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->findOrCreateUser($meData);

            $this->loginUser($user);
        }

        // ...
    }

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByCOOPId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            // a blank password - this user hasn't created a password yet!
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }

Try the process again. No error this time - we find the existing user and
use it instead of creating a new one.

Duplicate Emails
~~~~~~~~~~~~~~~~

There is one other edge-case. What if we *don't* find any users with this
COOP user id, but there *is* already a user with this email? This might be
because the user registered on TopCluck, but hasn't gone through the COOP
authorization process.

Pretty easily, we can do another lookup by email::

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByCOOPId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }

        if ($user = $this->findUserByEmail($meData['email'])) {
            // we match by email
            // we have to think if we should trust this. Is it possible to
            // register at COOP with someone else's email?
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            // a blank password - this user hasn't created a password yet!
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }

Cool. But be careful. Is it easy to fake someone else's email address on
COOP? If so, I could register with someone else's email there and then use
this to login to that user's TopCluck account. With something other than COOP's
own user id, you need to think about whether or not it's possible that you're getting
falsified information. If you're not sure, it might be safe to break the
process here and force the user to type in their TopCluck password for this account
before linking them. That's a bit more work, but we do it here on KnpUniversity.com.

Finishing Registration
----------------------

When you *do* have a new user, instead of just creating the account, you
may want to show them a finish registration form. This would let
them choose a password and fill out any other fields you want.

We've got more OAuth-focused things that we need to get to, so we'll leave
this to you. But the key is simple: store at least the ``coopAccessToken``,
``coopUserId`` and token expiration in the session and redirect to a registration
form with fields like email, password and anything else you need. You could
also store the email in the session and use it to prepopulate the form, or
even make another API request to ``/api/me`` to get it. When they finally
submit a valid form, just create your user then. It's really just like any
registration form, except that you'll also save the COOP access token, user
id, and expiration when you create your user.
