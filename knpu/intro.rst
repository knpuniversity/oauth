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
gave access.

Nope, one way or another, giving our password away is *not* the answer. Instead,
we need something that *acts* like a password, but that only gives OpenSky
access to do certain things on our behalf. We also need to be able to revoke
access to OpenSky without removing access to other sites that *should* still
have access to our account.

The answer to this puzzle is: a token. When we use the web in a browser,
we login with a username and password. But typically, when you use the API
of a site, you authenticate by passing a token with your request, often times
as an HTTP header. The API looks up your token and finds the user account
that it's attached to. Any action done with the API is now done *as* that
user. Depending on how the API works, that token may only allow you to take
certain actions on behalf of the user. For example, you may be able to request
the list of Facebook friends, but not actually post on the user's wall.

With this simple idea, everything becomes possible. In the case of OpenSky,
we simply need to create and give them a token that's tied to our Facebook
account and limited to the activities that we'd like them to be able to take
on our behalf. Later, if we need to give some some other site access to our
Facebook account, we'll create a different token. This gives us the power
to *revoke* each token independently and control exactly who does and does
not have access to our account.

OAuth is a standard for authorization, which aims to allow a client to access
server resources on behalf of a resource owner. Um, in other words, it's
a workflow that defines how we might authorize OpenSky to access our Facebook
account on our behalf. And at the simplest level, it defines how we create
and give that all-important access token to someone like OpenSky.

The good news? Since OAuth is a standard, once you master it, you've unlocked
the ability to integrate with many APIs, including Facebook, GitHub, Dropbox,
Google, Instagram, LinkedIn, Twitter and a lot more.

These days, it's pretty common for you to give one application access to
your account on some other website. For example, think of your Facebook account.
Ok, let's go!

NOTES

1. Intro (1:00)
 - The Why: “So you don’t have to give out your password”
 - The What: OAuth 2.0

- we should sell them briefly on why this is going to be awesome to know,
    such as easily offering Facebook/Google/etc login and integration on their
    sites and JavaScript integration with at least some of these (e.g. Facebook)

Passwords are a big deal! You don't want to give them out… ever!

The protocol exists to allow a 3rd party access to your protected resources without requiring you to trust that 3rd party with your password
