Security
========

Since TopCluck is handling a lot of access tokens for Brent's farmer friends,
he wants to make sure it's secure. Nothing would be worse than for the access
tokens of the TopCluck farmers to get stolen - allowing some city slicker to take control
of the good people's farms!

Exchanging tokens in a secure way isn't easy because there are a lot of opportunities
for a hacker to be listening to the requests or doing some other clever thing.

CSRF Protection with the state Parameter
----------------------------------------

Open up ``CoopOAuthController`` so that we can squash a really common attack.
In the authorize redirect URL, add a ``state`` parameter and set its value
to something that's only known to the session for *this user*. We can do
that by generating a random string and storing it in the session::

    // src/OAuth2Demo/Client/Controllers/CoopOAuthController.php
    public function redirectToAuthorization(Request $request)
    {
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $state = md5(uniqid(mt_rand(), true));
        $request->getSession()->set('oauth.state', $state);
        $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => 'TopCluck',
            'redirect_uri' => $redirectUrl,
            'scope' => 'eggs-count profile',
            'state' => $state
        ));

        return $this->redirect($url);
    }

Let's also add a ``die`` statement in the ``receiveAuthorizationCode`` function
that's executed after COOP redirects back to us::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        die;
        // ...
    }

Log out and click to login via COOP. Of course, when we redirect to COOP,
the new ``state`` parameter is there. Interestingly, after we authorize, COOP
redirects back to us and *also* includes that exact ``state`` parameter.

In ``receiveAuthorizationCode``, we just need to make sure that ``state``
matches the string that we set in the session exactly. If it doesn't, let's
render an error page: this could be an attack::

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        if ($request->get('state') !== $request->getSession()->get('oauth.state')) {
            return $this->render(
                'failed_authorization.twig',
                array('response' => array(
                    'error_description' => 'Your session has expired. Please try again.'
                ))
            );
        }

        // ...
    }

Using the ``state`` parameter is just like using a CSRF token with a form:
it prevents XSS attacks.

When we log in now, it all still works perfectly.

Imagine I start the authorization process, but use a browser plugin to prevent
COOP from redirecting me back to TopCluck. Then, I post the redirect URL with
my valid authorization code to a forum somewhere, maybe embedded in an image
tag. Assuming you're logged into TopCluck, when you view this page, the image
tag will make a request to TopCluck, which exchanges the authorization code
for an access token in the background.

So what? Well, ``CoopOAuthController`` would end up saving your
``coopUserId`` to the attacker's TopCluck account. This means when 
the attacker logs into TopCluck using COOP, they'll be logged in as *you*!

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
origins. These function the same way: to prevent JavaScript on some other
hostname from using your client id.

The Insecurity of Implicit
--------------------------

The implicit grant type is the least secure grant type because the access
token can be read by other JavaScript on your page and could be a victim
of XSS attacks. If you decide to use implicit, you must be *extra careful*
in preventing the attacks on the pages where access tokens are used in
JavaScript.

This is another example of why registering an exact redirect URI is important.
If an attacker locates just one XSS vulnerability on your site, they could
manipulate the redirect URI to point there, and use it to steal access tokens.
It's also even more important to validate your state parameter.

If it's at all possible to use the authorization code grant type instead, this is
much better because even if there was a man in the middle or piece of JavaScript
reading your authorization code, the client secret is still needed to turn that into
an access token.

One interesting thing about the implicit grant type is that the access token
is passed back as a URL fragment instead of a query parameter:

    http://localhost:9000/coop/oauth/handle?code=abcd123
    http://localhost:9000/coop/oauth/handle#access_token=wxyz5678

We didn't see this with Google+ because it was all being handled in the background
for us. But this is really important because anything after the hash in a
URL isn't actually sent when your browser requests a page. The JavaScript
on your page can read this, but since it's not sent over the web, anyone
listening between the user and the server won't be able to intercept it.
That's not as important with the code, because the man-in-the-middle would still
need the client secret to do anything with it.

Https
-----

An important piece of OAuth security is using SSL. This means all requests to an
OAuth server should be done using HTTPS. The reason is that the ``access_token``,
is always sent in plain text. That's true when the OAuth server first gives
us the access token and on *every single* API request we make back afterwards.
This makes using OAuth APIs much more convenient for us developers, but if
those requests aren't encrypted, you're asking for a fox in your hen house.

And when you make those calls over HTTPS, make sure you actually verify the SSL
certificate. Your HTTP library will do this for you, but it will also give you
the option to skip verification. This is tempting when developing locally or if
you get an error like:

    Peer certificate cannot be authenticated with known CA certificates

But don't disable verification! That's like keeping the door open on your chicken
coop! Turning off SSL Verification is the same as sending the access token
unencrypted. Don't manually turn this off and you'll be okay.

Interestingly, *your* site doesn't technically need to use HTTPS. When the
user is redirected back with the auth code, it's ok if someone intercepts this,
since they won't also have your client secret.

But any time you have a logged in user, you should really use HTTPS. Without
it, your user's session could be stolen by someone else on the same network!
And all your hard work making your OAuth implementation secure will go to
waste.

Authentication with OAuth
-------------------------

In our tutorial, we allow people to log in with COOP and Facebook. But this
isn't the purpose of OAuth. Usually, we think that the only way for us to
get an access token is for *that user* to give it to us directly via the
authorization process. So when we're given an access token for Brent's account,
we think "This must be Brent, let's log him into his TopCluck account".

With this authorization code grant type and the state parameter, this is
safe. But suppose instead that we decide to use the implicit flow in JavaScript.
After success, we'll send the new ``access_token`` via AJAX to the TopCluck
server and authenticate the user by looking up the ``coopUserId`` associated
with the token.

Now, what if some other site also allows you to authorize your COOP account
with them. They now also have an access token for your COOP account. If they're
nasty, or if your ``access_token`` gets stolen, someone could pass it directly
to our AJAX endpoint and become authenticated on TopCluck in your account.

That's right - any site that has an access token to your Coop or Facebook
account could use it to log into any other site that has this flawed login
mechanism.

The moral is this: since OAuth is not meant for authentication, you need
to be extra careful when you do this. Most importantly, stay away from
the implicit grant type for authenticating users, as we have done in this
tutorial.

The End
-------

Our hero Brent's life is a lot better than when we started. Thanks to his
CRON script, his chickens are getting fed everyday. And with the TopCluck
site, he's well on his way to victory over farmer Scott *and* sharing his
glory all over Facebook. All of this was possible by getting a deep understanding
of OAuth, which unleashed us to do all kinds of interesting integrations
with third-party sites. I know that you will have just as much success as Brent!

See you next time!
