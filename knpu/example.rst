Example Time
============

It's the distant future, the year 2014, and someone has created an amazing
site that lets you control everything in your house called HouseRobot, or HR.
Just create an account, fill in your address, and magically, HR can do just
about anything with your house. Forget to lock the door? Just lock it from
here! What about flushing the toilet? Let's just flush it again to be sure.
Maybe you live in a farm and need to feed the chickens from the road. Yep,
HR can handle all of this.

Life is great until we watch "MOVIE" and decide that we deserve a beach vacation
where we completely unplug. That's right, no computers, no internet, no phones.

But who will feed our chickens while we're unplugged? HR is great, but we
can't get off the beach to login every day! In fact, more and more people
start complaining about certain missing features to HR: "Instead of logging
in and clicking a link at 5 am, why can't my chickens just get fed automatically?

Thankfully, we're developers! So, we decide to build our own app that allows
people to schedule house activities automatically. Just come to our site,
give us access to your HR account, schedule an activity, and you're done!

Let's imagine that we've already built the site and some guy from the internet,
let's call him Brent, wants to use it. In order for our site to take control
of his house, we need access to his HouseRobot account. So, let's just ask
him for his username and password. We can store it in the database and then
use it whenever we need to feed his chickens or flush his toilet.

Except, why would this guy want to give us, a bunch of strangers, his username
and password? He wouldn't give us a copy of the keys to his house, so why
give us access to his HR account? How does he know we won't unlock the door
in the middle of the night or clog his toilet during a party? Maybe he only
wants us to be able to feed his chickens. But with his username and password,
we can do anything, Heck, we could even change his password!

That clearly won't work. And really, that's good for us too. If someone gained
access to our database, they'd have the HR username and password to every
user in our system. Toilets would be flushing wildly all over the world, and
we'd need to send out a big ugly security email about it.



What if HumanRobot let Brent create long alphanumeric

Instead of his username and password, what if Brent gave us a long, secret,
alphanumeric key that we could use in place of his username and password.



ORIGINAL NOTES
--------------

- go through drawings more
- talk about giving away password

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