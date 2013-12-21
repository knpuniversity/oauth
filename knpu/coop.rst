The Chicken Oversight Operations Platform (COOP)
================================================

First, let's take a quick look at the COOP site. You can see the application
by going to `http://coop.apps.knpuniversity.com`_. It's pretty simple and
awesome: we can create an account in order to start controlling things on our
farm. For most people, this is all they need: a website where you can login
and start commanding your farm from a distance. Never again will you forget
to feed the chickens!

COOP also has an API. It's simple, consisting of a few POST endpoints.
To use it, you need to include an access token in the API request. Exactly how
this is done depends on the API, but the most common method for APIs that
support OAuth is to send it via an Authorization Bearer header.

.. code-block:: text

    GET /api/barn-unlock HTTP/1.1
    Host: coop.apps.knpuniversity.com
    Authorization: Bearer ACCESSTOKENHERE

This token takes the place of your username and password, is tied to exactly
one COOP account, and is limited to performing certain tasks.

For our convenience, the COOP has a little sandbox for each POST endpoint, and as you
can see, the only thing we need to specify is the Access Token. Now, how
do we get that access token?

Creating an Application
-----------------------

Now let's create an application.

- explain the application
- create the application
- set the scope
- grab an access token
- try collecting eggs online, then doing something else

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

.. _`http://coop.apps.knpuniversity.com`: http://coop.apps.knpuniversity.com