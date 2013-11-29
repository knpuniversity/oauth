Example Time
============

Woohoo! You've just won an all-expense paid 7 day beach vacation to the
Carribean!

Meet Sally. She's a hardworking programmer that hasn't seen the sun in years.
But that's about to change, with a 7 day beach vacation with her favorite
friend Brent. No computers, no phones, no internet, just sunshine, palm trees,
and clear water.

  *show Sally and Brent on a beach with all these nice things*

But there's just one problem... someone needs to feed the chickens! Brent,
of the beard-growing, organic farming variety, has a coup of the nicest, smartest,
but hungriest chickens around. Unless we find someone to feed these guys
and teach them algebra, we can't have any vacation!

  *shows Brent reading a book to chickens that are thinking about either food or math equations*

With a quick search, we find "Handy Hal's Housing Help": a guy in the internet
who offers services to your house when you're away. Perfect!

  *Photo of Hal, potentially looking a little greasy if possible*

But, do you really want to give this guy a copy of your keys? All you really
want to give him access to is the chicken coup, not the full house. And what
if he loses your keys? You'd have to change the locks. And what if he creates
another copy of your keys and keeps them?

  *Drawing: Keys (with no-smoking sign)*

What if the doors on your house and chicken coup not only took a key, but
also took a temporary card, like of like a hotel room. You can issue out
these cards to certain people you trust, and they expire after a limited time.
Just like a hotel key-card, each card can only open certain doors. This would
be *perfect*. And because Sally is a programmer, she calls each card an "ACCESS TOKEN".

Chickens and Vacation on a Web App
----------------------------------

Ok, instead of thinking about Brent's house and some guy named Hal, let's
turn to the web! Imagine it's the distant future, the year 2014, and someone
has created a web site where you can register, verify your home address,
then completely control anything in your house. It's called HouseRobot, and
you can do anything from feeding the chickens to flushing the toilet.

If Brent signs up, he could feed his chickens right from the web. That's nice,
but with his laptop at home, and the pina coladas on the beach, he won't
even have access to HumanRobot while on vacation.

Sally, being the programming master-mind, sees an opportunity: a site where
you can schedule house tasks ahead of time, like feeding the chickens each
day at 5 am. When that time comes, the site can use your HumanRobot account
on your behalf to actually do things. Genius!

But if Sally makes the users to the site enter their HouseRobot username and
password, that's just as bad as making someone give you a copy of their house key.
Sally seem nice, but her site could do anything to your house, like unlocking
the doors at night or, worse, clogging your toilet during a part! And if
someone got access to her database, someone nasty would have complete control
over everyone's home!

In this screencast, we'll play the part of Sally, using OAuth to avoid this
situation. Instead of a username and password, our users will give us a token
that grants us temporary access to control only certain parts of their house
through HouseRobot. We'll learn about the different OAuth grant types, from
the classic auth code flow to the implicit flow that's commonly used by JavaScript
or mobile apps. And of course we'll talk about more advanced things like handling
refresh tokens and include a practical example of connecting with Facebook.

Let's go!




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