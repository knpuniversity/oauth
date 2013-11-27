The Farm Robot Project
======================

Let's pretend that this HouseRobot site is already totally functional and
everyone loves it. You can see the application by going to `http://houserobot.knpuniversity.com`_.
It's pretty simple: we can create an account, and then start controlling
things on our house. For most people, this is all they need: a website where
you can login and start commanding your house from the distance. Never again
will you leave the toilet seat down!

HouseRobot also has an API. It's pretty simple, consisting of a few POST
endpoints. To authenticate, you pass an authentication token in the header
of the request. This token takes the place of your username and password
and is tied to exactly one HouseRobot account with limited access to perform
certain tasks. If we're writing another website or a mobile app and we want
users to give us access to their HouseRobot account, we'll need a valid token
from them. OAuth is all about exactly *how* we get that token, what it allows
us to do, and all the workflow around that.

The Client Application
----------------------

So let's look at *our* site, which will interact with HouseRobot's API. Go
the the GitHub repository for this tutorial and clone or download the code.
Inside, you'll see a ``client-start`` directory, which is a very simple
starting point for our new app written in Silex, a PHP microframework. If
you're not familiar with Silex, don't worry! We won't interact with it too
much and we'll explain things along the way.

To run the application, open up your terminal and move into this directory.
From here, keep going into the ``web/`` directory, which is the document
root of the project. Here, we'll use the built-in PHP web server to get the
site running quickly:

.. code-block:: bash

    php -S localhost:8000

When we go to ``http://localhost:8000``, we see a starting client app. Awesome!
It doesn't do much yet, but we'll fix that!

The Client Credentials Flow
---------------------------

OAuth has a few different variations, called grant types. A grant type is
basically a strategy for *how* we get the all-important access token that
lets us make API requests on behalf of some user. Each grant type has different
use-cases as we'll see.

- go on to think of a good use-case for showing client credentials. This
    is nice because it's simple: just get the access token. Then, show
    how we could go immediately to our client app and start using it.


Let's take a look at the HouseR

- get the server running
- show us registering on the server for our account and controlling things
    in the site. Yay!
- check out the server's API and grab a client manually?
    -> client credentials flow!?
- Show the client. Show how there are buttons *ready* to make requests to
    the server on your behalf, but that they don't work yet :/ - we don't
    have a key!

- go manually grab your key and add it to your account. Everything works!


- talk about how this flow could be easier for the user, introduce the
    whole flow - from redirecting, access token, authorization token, etc


# The OAuth Server

## Introduce Existing Server
    - Start the server locally
        - browse to `server-finish\web`
        - run `php -S localhost:9000`
    - browse to `http:\\localhost:9000` in your browser
    - This is your "house" website
    - click "unlock door"
        - notice how an `Access Token` is required
    - click "Authorization Code"
        - notice how a "client ID" is required
    - click on "create one" to create an application

.. _`http://houserobot.knpuniversity.com`: http://houserobot.knpuniversity.com