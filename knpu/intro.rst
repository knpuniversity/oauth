Serious OAuth in 8 Steps
========================

Hey guys and gals! In this tutorial, we're going to get serious with OAuth by building
an app with some complex and real-life features, like Facebook authentication,
dealing with refresh tokens and more. We'll need about 8 steps to turn
a barebones starting app into a complex, OAuth machine:

1) Client Credentials: making API requests for our own account
2) Authorization Code: Getting a token for another user's account
3) Logging in via OAuth
4) OAuth with Facebook
5) OAuth in JavaScript with Google+
6) Handling Expired Tokens
7) Using Refresh Tokens
8) Tightening up Security

As we go through these, we'll give you any theory and background you need.

Tiny Crash Course in OAuth
--------------------------

For now, you just need to understand that OAuth is an Authorization Framework.
In human-speak, it means that it defines the different ways two parties,
like your cool web site and a *user* on your website, can exchange tokens
securely. Each of these ways is known as a grant type and though they look
different, each grant type will always deliver an access token. 

OAuth Token
~~~~~~~~~~~

So what's this token? It's just a unique string tied to my account that gives
*you* access to make API requests on my behalf. It's like a username and
password all rolled into one. For example, if ``ABCD1234`` is a valid token
to my Facebook account, then an HTTP request that looks like this would post
to my timeline:

.. code-block:: text

    POST /weaverryan/feed HTTP/1.1
    Host: graph.facebook.com
    Content-Type: application/x-www-form-urlencoded
    Content-Length: length

    access_token=ABCD1234&message=Hello

Exactly *how* you pass the access token in an API request is different between
Facebook, Twitter, or any other API. But it's always there 

I *could* just give you my username and password, but a token is much better.
If I give 10 apps access to my account, each app will have its own token,
which means I can revoke access to some apps, but not others.

Tokens can have a limited scope, which is *huge*. Unlike a password which
gives you access to do *anything* on my account, I can give you a token that
lets you view my Facebook friends, but not post to my wall.

So OAuth is really just a big set of rules that describe how two parties can 
exchange tokens. If I create a website where I want to access my users' Facebook 
friends, exactly how does a user *give* me an access token?

Let's answer that question, along with the thrilling topic of token expiration,
the hopeful story of refresh tokens, the inspirational tale of single-sign on
and all kinds of other things.

Let's go!
