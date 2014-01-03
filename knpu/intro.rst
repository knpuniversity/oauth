Serious OAuth in 8 Steps
========================

Hey guys! In this tutorial, we're going to get serious with OAuth by building
an app with some complex and real-life features, like Facebook authentication,
dealing with expiration tokens and more. We'll need about 8 steps to turn
a barebones starting app into a complex, OAuth machine:

1) Client Credentials: making API requests for our own account
2) Authorization Code: Getting a token for another user's account
3) Logging in via OAuth
4) OAuth with Facebook
5) OAuth in JavaScript with Facebook
6) Handling Expired Tokens
7) Using Refresh Tokens
8) Tightening up Security

As we go through these, we'll give you any theory and background you need.

For now, you just need to understand that OAuth is an Authorization Framework.
What that *actually* means is that it defines the different ways in which
two parties - like your neat web site and a user on your website - can exchange
tokens securely. Each of these ways is known as a grant type, and each has
a unique way of requesting an access token. Although the requests look different,
a grant type will always resolve to an access token. 

A token just a unique string that I can give you, which will give you access
to make API requests on my behalf. It acts like a username and a password
all rolled into one. For example, if ``ABCD1234`` is a valid token to my
Facebook account, then an HTTP request that looks like this would post to
my timeline:

.. code-block:: text

    POST /weaverryan/feed HTTP/1.1
    Host: graph.facebook.com
    Content-Type: application/x-www-form-urlencoded
    Content-Length: length

    access_token=ABCD1234&message=Hello

Exactly *how* you include the access token in an API request will be different
between Facebook, Twitter, or any other API. But it's always there.

Of course, I could just give you my username and password. But a token is
better for a few reasons. First, if I give 10 apps access to my account,
each app will have its own token, which means I can revoke your access without
revoking everyone's access.

Second, and even more important, tokens can have a limited scope. So unlike
a password, I can give you a token that gives you access to view my Facebook
friends, but not post to my wall.

So going back, OAuth defines different ways that two parties can exchange
tokens. If I create a website where users give the site access to list their
Facebook friends, exactly how does those users *give* that token to the site?

The answer to that question, along with token expiration, refresh tokens
and things called grant types, make this a journey well-worth taking.

Let's go!
