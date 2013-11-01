Example Time
============

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