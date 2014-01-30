Implicit Grant Type with Google+
================================

With Facebook integration done, Brent's super happy because he can post his
major egg-collecting success on Facebook for all his farmer friends to see.

Now he wants to go a step further and let people invite their Google+ connections
to signup for a TopCluck account and get in on the fun. To make it really
hipster, he wants to do this entirely on the frontend with JavaScript. The
user will click a button to authorize their Google+ account, see a list of
their connections, and select which ones to invite - all without any page
reloads.

The Implicit Grant Type
-----------------------

So far we've seen 2 different grant types, or strategies for exchanging the
access token. These were client credentials and authorization code. Unfortunately,
neither works inside JavaScript. The problem is that both ultimately involve
making a request to the token endpoint (e.g. ``/token``) and including your
client secret. As the name suggests, that string is a secret. So, printing
it inside an HTML page and using it in JavaScript would be a terrible idea.

Instead, we need to look at one more grant type called implicit. It's a lot
like authorization code, but simpler. With authorization code, the user is
redirected back to our app with an authorization code. We make another API
request to the token endpoint to exchange it for an access token.

With the implicit flow, when the user is sent back to us, they come with
the access token directly - instead of the authorization code. This eliminates
one step of the process. But it also has some disadvantages as we'll see.

JavaScript OAuth with Google+
-----------------------------

To integrate with Google+, let's start by finding their `JavaScript documentation`_.
Just like with Facebook, Google+ has JavaScript SDK that's going to do most
of the work for us. But if we watch closely enough, we'll see OAuth happening.

TODO

.. _`JavaScript documentation`: https://developers.google.com/+/quickstart/javascript

-- how are the client-side API requests being made behind the scenes?
-- how does Facebook's JavaScript implementation differ and how does this
    relate (or what should we mention about) the code versus token response
    type when doing the authorization redirect.
-- mention no refresh token