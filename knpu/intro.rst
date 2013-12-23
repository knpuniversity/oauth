Introduction!
=============

Hey guys! In this tutorial, we're going to get dirty with OAuth. So what
is OAuth anyways?

These days, it's pretty common for an application or website to ask us to
give it access to one of our accounts. For example, if I log into OpenSky,
an e-commerce website, I can invite my Facebook friends. Of course to do
this, OpenSky needs access to my Facebook account so it can see who all of
my friends are.

So how could we give OpenSky access to our account? OpenSky could just ask
for our Facebook password. With that, it could login on our behalf and grab
whatever information it needed.

But that would be nuts! Our password gives OpenSky access to do *anything*,
like read our friends, make new friends, post for us, or even change our
password. And the only way to remove OpenSky's access would be to change
our password, which would also remove access to every other site that we
have given access.

Nope, one way or another, giving our password away is *not* the answer. Instead,
we need something that *acts* like a password, but only gives OpenSky
access to do certain things on our behalf. We also need to be able to revoke
access to OpenSky without removing access to other sites.

The answer to this puzzle is: a token. When we use the web in a browser,
we login with a username and password. But typically, when a service uses the API
of a site, it authenticates by passing a token with the request, often times
as an HTTP header. The API looks up this token and finds the user account
that it's attached to. Any action done with the API is now done *as* that
user. Depending on how the API works, that token may only be allowed certain
actions on behalf of the user. For example, the token might be able to access
the list of Facebook friends, but not actually post on the user's wall.

With this simple idea, everything becomes possible. In our example, we simply
need to give OpenSky a token which is tied to our Facebook account, and limited
to the activities we'd like them to perform
on our behalf. Later, if we need to give another site access to our
Facebook account, we'll create a different token. This gives us the power
to *revoke* each token independently and control exactly who does and who does
not have access to our account.

So what is OAuth? OAuth is defined as an Authorization Framework. This means
it defines the different ways in which the exchanging of tokens can be done
securely. In our case, it defines how we create and give that all-important
access token to someone like OpenSky.

Because authorization requirements vary between applications, OAuth defines
multiple ways the creation of access tokens can be done. We will refer to
these different ways as OAuth flows.

Since OAuth is a standard, once you master it, you've unlocked
the ability to integrate with many APIs, including Facebook, GitHub, Dropbox,
Google, Instagram, LinkedIn, Twitter and a lot more.

Ok, let's go!
