Life On the Farm
================

Meet Brent. He's the hardworking, beard-growing, organic farming type who
has a coup of the nicest, smarter, and best egg-laying chickens on this side
of the Mississippi! But feeding his chickens and doing other thing around
the farm have always taken a lot of time.

  *show Brent being a hero on his farm*

But great news! The brand new "Chicken Oversight Operations Platform", or
COOP site has just launched! With COOP, you can just login to the site and
collect your chicken eggs, unlock the barn, and do all kinds of other things
just by clicking a button.

  *show Brent on a computer, and some big robot collecting chicken eggs*

With all his free time, Brent also learns how to program and starts learning
about COOP's awesome API, which uses OAuth 2.0. And as easy as it is now
to collect his eggs, he wonders if he could write a little script that would
do it automatically. Yea, if we had a script that made an API request on his
behalf, he could run it on a CRON job daily and sleep in!

  *show Brent having a Eureka moment or imagining himself sleeping while his computer runs a CRON*

Suddenly, Brent is jolted from his dreaming to the sound of farmer Scott driving
his eggs to the market and screaming "Haha, Brent! My chickens lay way more
eggs than yours!" But in reality, Brent *knows* that his chickens are way
better egg-making hens than Scott could ever hope for! But how to prove it?

Then it hits him! Fantasy Chicken League: a new website that Brent will build
that will keep track of *exactly* how many chickens come from each farm!
The COOP API defines an endpoint that allows you to see how many eggs have
been collected from a farm each day. In Fantasy Chicken League, each farmer
will sign up, give FCL access to his COOP account, and then FCL will count
exactly how many eggs that farm is collecting. And finally, Brent will be
king of the hipster chicken farmers!

In this tutorial, we'll play the part of Brent, using OAuth first to gain
access to his account for a simple script that uses the COOP API to collect
his eggs. From there, we'll learn all about the most well-known OAuth flow
and use it to allow farmers to authorize our Fantasy Chicken League website
to count their eggs.

We'll also learn about the implicit flow that's commonly used by JavaScript
or mobile apps and of course more advanced things like handling refresh tokens.
With all this, we'll show some practical examples of leveraging our expertise
to connect with Facebook on the server-side and via its JavaScript SDK.

Ok, let's go farming!

NOTES
-----

- Maybe introduce more terms here (see below)? Or perhaps we can do this
as things come up later.

# Analogy

## Allowing a stranger to feed your chickens

Let's say you go on a vacation with your spouse / significant other.

  *Drawing: Leaving on Vacation*

and you forget to feed your chickens!

  *Drawing: Hungry Chickens*

Well, there's some guy on the internet who offers services to your house when you're away (Handy Hal's Housing Help), such as feeding chickens.

  *Drawing: Hal*

You're in luck!!  Except that you don't want this stranger to have a copy of your keys, right??

  *Drawing: Keys (with no-smoking sign)*

What if your house door not only took a key, but it also took a temporary card, kind of like a hotel might have.  You can issue out these cards to certain people you trust, and they expire after a limited time.  Just like a hotel key-card, each card can only open certain doors.  This is an ACCESS TOKEN.

  *Drawing: card distribution to multiple people for different doors*

What if your house had a website where the door could be unlocked by providing the card, or access token, in the browser?  This is OAuth.

  *Drawing: thumbs up?*

 You: “Hal, go to my house's website.  Create an account and specify what you want to do (unlock the door).  Then, I will retrieve a secret passphrase from the house.  I'll give you the passphrase, you can exchange this code with the house for a keycard, and you can get in!"

## Diagrams

1. Arrows for Exchanging as described above (w/ Hal/house/you)

2. Explanation of Terms:

    You             => Resource Owner (a.k.a. End User)
    House           => Resource (a.k.a. APIs)
    "Door Unlock"   => Service Call
    Keys            => Your Password
    Hal             => The Client (a.k.a. 3rd Party)
    House’s Website => Service Provider / Authentication Provider / OAuth2.0 Server
    Passphrase      => Authorization Code
    KeyCard         => Access Token

3. Arrows for Exchanging using OAuth terms