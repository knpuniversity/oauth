Security
========

Since TopCluck is handling a lot of access tokens for his farmer friends,
he wants to make sure it's secure. Nothing would be worse than for the access
tokens of his users to get stolen - allowing some outsider to take control
of people's farms!

Exchanging tokens in a secure way isn't easy because there are a lot of opportunities
for a hacker to be listening to the requests or doing something else clever.

CSRF Protection with the state Parameter
----------------------------------------

Open up ``CoopOAuthController`` so that we can squash a really common attack.
In the authorize redirect URL, add a ``state`` parameter and set its value
to something that's only known to the session for *this user*. We can do
that by generating a random string and storing it in the session.

    TODO: Code: Security: Passing the state parameter

Let's also add a ``die`` statement in the ``receiveAuthorizationCode`` function
that's executed after COOP redirects back to us::

    TODO: Code: Security: Passing the state parameter

Log out and click to login via COOP. Of course, when we redirect to COOP,
the new ``state`` parameter is there. Interestingly, after we authorize, COOP
redirects back to us and *also* includes that exact ``state`` parameter.

In ``receiveAuthorizationCode``, we ust need to make sure that ``state``
matches the string that we set in the session exactly. If it doesn't, let's
render an error page: this could be an attack::

    TODO: Code: Security: Checking the state parameter

When we log in now, it all still works perfectly.

Using the ``state`` parameter is just like using a CSRF token with a form:
it prevents XSS attacks. Imagine I start the authorization process, but use
a brower plugin to prevent COOP from preventing me back to TopCluck. Then,
I post the redirect URL with my valid authorization code to a forum somewhere,
maybe with embedded in an image tag. Assuming you're logged into COOP, when
you view this page, the image tag makes a request to COOP, which exchanges
the authorization code for an access token in the background.

So what? Well, in addition to getting the access token, ``CoopOAuthController``
would also save the attacker's ``coopUserId`` to *your* TopCluck account.
I can now log into TopCluck using my COOP account. And when I do, I'll be
logged into *your* TopCluck account.

So, *always* use a ``state`` parameter. Fortunately, when you work with something
like Facebook's SDK, this happens automatically. We didn't realize it, but
it was generating a state parameter, saving it to the session, and checking
it when we exchanged the authorization code for the access token. That's
pretty nice.

Registering the Redirect URI
----------------------------

Head over to COOP and check out our application there. One field we left
blank was the Redirect URI. Let's fill it in now with a made-up URL.

Try logging in again. This time, we immediately get an error from COOP:

    The redirect URI provided is missing or does not match

The redirect URI is a security measure that guarantees that nobody can use
your client ID, which is public, to authorize users and redirect with the
authorization code or access token back to *their* site. Many OAuth servers
require this to be filled in. In fact, we saw that with Facebook earlier

I'll re-edit the application and put in our exact ``redirect_uri`` value.
When we try to login in now, it works.

Most OAuth servers will require this value. Sometimes, the URL we put here
must match the ``redirect_uri`` parameter *exactly*. Other times, it's a
fuzzy match. This is up to the OAuth server you're using, but exact matching
is much more difficult to fake.

In a client-side environment where the code or token is passed via JavaScript,
the OAuth server may just ask you for your hostname or a list of JavaScript
origins. These function the same way: to prevent JavaScript form any other
hostname from using your client id.

The Insecurity of Implicit
--------------------------

The implicit grant type is the least secure grant type because the access
token can be read by other JavaScript on your page and could be a victim
of XSS attacks.

There's not much you can do about this,, other than setting your redirect
URI or using the authorization code grant type instead. This is better because
even if there was a man in the middle or piece of JavaScript reading your
authorization code, the client secret is still needed to turn that into an
access token.

One interesting thing about the implicit grant type is that the access token
is passed back as a URL fragment instead of a query parameter:

    http://localhost:9000/coop/oauth/handle?code=abcd123
    http://localhost:9000/coop/oauth/handle#access_token=wxyz5678

We didn't see this with Google+ because it was all being handled in the background
for us. But this is really important because anything after the hash in a
URL isn't actually sent when your browser requests a page. The JavaScript
on your page can read this, but since it's not sent over the web, anyone
listening between the user and the server won't be able to intercept it.
That's not important with the code, because the man-in-the-middle would still
need the client secret to do anything with it.

Like this illustrates, the biggest challenge with OAuth security is thinking
about who else might be able to read your access tokens - whether it's some
JavaScript on your page or someone reading the traffic between your user
and your server.

Https
-----

A big part of OAuth security is using https. Actually, the most important
thing is that you always communicate with the OAuth API using ``http``. In
fact, most OAuth servers *only* allow https. The reason is that the ``access_token``,
is always sent in plain text. That's true when the OAuth server first gives
us the access token and on *every single* API request we make back afterwards.
If those requests aren't encrypted, you're asking for trouble.

Interestingly, *your* site doesn't technically need to use https. When the
user is redirected back with the authorization code, it's ok if someone reads
this, as long as your client secret stays very safe.

Of course, there are a lot of other good reasons in general to use https,
so don't let this be an excuse not to!

Authentication with OAuth
-------------------------

In our tutorial, we allow people to log in with COOP and Facebook. But this
isn't the purpose of OAuth. Usually, we think that the only way for us to
get an access token is for *that user* to give it to us directly via the
authorization process. So when we're given an access token for Brent's account,
we think "This must be Brent, let's log him into his TopCluck account".

With this authorization code grant type and the state parameter, this is
safe. But suppose insetad that we decide to use the implicit flow in JavaScript.
After success, we'll send the new ``access_token`` via AJAX to the TopCluck
server and authenticate the user by looking up the ``coopUserId`` associated
with the token?

Now, what if some other site also allows you to authorize your COOP account
with them. They now also have an access token for your COOP account. If they're
nasty, of if your ``access_token`` gets stolen, someone pass it directly
to our AJAX endpoint and become authenticated on TopCluck in your account.

That's right - any site that has an access token to your Coop or Facebook
account could use it to log into any other site that has this flawed login
mechanism.

The moral is this: since OAuth is not meant for authentication, you need
to be extra careful when you do this. Fortunately, it's a well-established
pattern that tons of sites use, just be cautious when you do it!

The End
-------

Our hero Brent's life is a lot better than when we started. Thanks to his
CRON script, his chickens are getting fed everyday. And with the TopCluck
site, he's well on his way to victory over farmer Scott *and* sharing his
glory all over Facebook. All of this was possible by getting a deep understanding
of OAuth, which unleashed us to do all kinds of interesting integrations
with third-party sites. I hope you have as much success as Brent has!

See you next time!
