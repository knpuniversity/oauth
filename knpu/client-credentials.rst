Client Credentials
==================

Meet Brent. He's the hardworking, beard-growing, kale-munching type who
has a coop of the nicest, smartest, and best egg-laying chickens this side
o' the Mississippi! But feeding his chickens and doing other things around
the farm has always taken a lot of time.

  *show Brent being a hero on his farm*

But great news! The brand new "Chicken Oversight Operations Platform", or
COOP site has just launched! With COOP, you can login to the site and
collect your chicken eggs, unlock the barn, and do all kinds of other things
just by clicking a button.

  *show Brent on a computer, and some big robot collecting chicken eggs*

Noticing that COOP has an API, he wonders if he could write a little script
that would collect his eggs automatically. Yea, if he had a script that made
an API request on his behalf, he could run it on a CRON job daily and sleep
in!

  *show Brent having a Eureka moment or imagining himself sleeping while his computer runs a CRON*

So COOP is real, sort of. You can find this make-believe website by going
to `http://coop.apps.knpuniversity.com`_. Go ahead and create an account,
and start controlling your virtual farm. It's the future!

Access Tokens in the API
------------------------

COOP's API is simple, with just a few POST endpoints. To use it, you need
to include an access token in the API request. Exactly how this is done depends
on the API, but the most common method for APIs that support OAuth is to
send it via an Authorization Bearer header.

.. code-block:: text

    GET /api/barn-unlock HTTP/1.1
    Host: coop.apps.knpuniversity.com
    Authorization: Bearer ACCESSTOKENHERE

This token takes the place of your username and password, is tied to exactly
one COOP account, and is limited to performing certain tasks.

For our convenience, the COOP has a little sandbox for each POST endpoint, and as you
can see, the only thing we need to specify is the Access Token. Now, how
do we get that access token?

Fortunately, the COOP API supports OAuth, which means that it follows
a standard for how this is done. What's a little confusing is that there
isn't just one strategy for exchanging access tokens with OAuth, there
are several. These strategies are called *grant types*, and each is
perfect for different situations, like web apps versus desktop apps.

OAuth Applications
------------------

But first, we need to register an application on COOP. The application represents
the third-party app or website that will attempt to get access to a user's
account. Let's give it a name like "Brent's Lazy CRON Job", a description,
and check only the box for "Collect Eggs from Your Chickens". These checkboxes
are called "scopes", which are basically permissions. In this case, if a
user grants this application access to their account, the access token we
receive will only let us collect eggs from their account.

When we finish, we now have a Client ID and an auto-generated "Client Secret".
These are a sort of username and password for the application.

When we finish, the applicaiton's name appears as the "Client ID" and we
have an auto-generated "Client Secret". The "client" in OAuth terminology
represents the application intending to access the protected resources. In
our examples, it is safe to assume "client" is referring to your application.

Client Credentials Grant Type
-----------------------------

The first OAuth grant type is called Client Credentials, which is the simplest
of all the types. It involves only two parties, the client and the server.
The client makes a direct request to the server, provides the client ID
and secret, and recieves an access token in return. The access token is limited
to the resources under the control of the client. This is perfect for routine
service calls such as the one in this example. There is no third party or
end user in this grant type, which simplifies the exchange.

Every OAuth server has an API endpoint where you can retrieve an access token
for this use-case. In fact, COOP gives us a nice little link to retrieve an
access token.

    Click the "Get an Access Token!" link on your application page

This token can be used immediately to take actions on *our* account via the API.
In fact, let's copy it, go to the sandbox for collecting eggs and enter it into
the form. And just like that, COOP collects our eggs!

Now try to perform the call to put the toilet seat down with the same access
token. Because we did not give our application this scope, the error "The
request requires higher privileges than provided by the access token" is shown.

Retrieving an Access Token
--------------------------

Let's go back and look at the link that gave us the access token:

.. code-block:: text

    http://coop.apps.knpuniversity.com/token
        ?client_id=CRON+Job+App
        &client_secret=4000ffca850007e43faffc81dda09942
        &grant_type=client_credentials

This is the all-important endpoint for retrieving tokens. The URL and parameters
may differ across OAuth implementations, but there are always just 3 important pieces:

1. The ``grant_type`` says that we're using the client_credentials. This
   says that we want to simply access *our* account: the account associated
   with the application. Later, we'll use other grant types to access other
   people's accounts.

2/3. The ``client_id`` and ``client_secret`` parameters identify and authenticate
   us as owners of the application.

Retrieving an Access Token in PHP
---------------------------------

Let's use this URL to get the access token from a PHP script.

Start by downloading the code from this repository and going to the ``start/``
directory. We've already prepared a ``cron/`` folder with a ``collect_eggs.php``
script to get us started.

We'll use a PHP library called `Guzzle`_ to help make API requests, instead
of PHP's native curl functions directly. If you haven't used Guzzle, it's
ok, it's easy! But first, `get Composer`_ and then use it to download the
Guzzle:

.. code-block:: text

    $ curl -sS https://getcomposer.org/installer | php
    $ php composer.phar install

.. note::

    Never heard of Composer or not comfortable with it? Watch our `free screencast`_.

Open up the ``collect_eggs.php`` file. As you can see, it uses a Guzzle Client
object, which is all setup and ready to make a POST request to the ``/token``
URL of COOP. All we need to do is fill in the ``client_id``, ``client_secret``
and ``grant_type``::

    // collect_eggs.php
    include __DIR__.'/vendor/autoload.php';
    use Guzzle\Http\Client;

    // create our http client (Guzzle)
    $client = new Client('http://coop.apps.knpuniversity.com');

    $request = $client->post('/token', null, array(
        'client_id'     => '',
        'client_secret' => '',
        'grant_type'    => '',
    ));

.. note::

    In OAuth, the token endpoint responds only to POST requests, since it
    creates a new token. In COOP, a GET request technically also works, but
    that's only to make the API demo easier to play with.

Let's copy the ``client_id`` and ``client_secret`` and set the ``grant_type``
to ``client_credentials``::

    // ...
    $request = $client->post('/token', null, array(
        'client_id'     => 'CRON Job App',
        'client_secret' => 'SECRET',
        'grant_type'    => 'client_credentials',
    ));

Send the request with the ``send()`` method, set its return value to a ``$response``
variable, and print its body so we can see what we get back::

    // ...
    $response = $request->send();
    echo $response->getBody(true);die("\n\n");

Try this by running ``collect_eggs.php`` from the command line:

.. code-block:: text

    $ php collect_eggs.php

With any luck, you should see an output that looks like this:

.. code-block:: json

    {
        "access_token": "75083959437f054e0f67f39c02d5d2d9485a890b",
        "expires_in": 3600,
        "token_type": "Bearer",
        "scope": "eggs-collect"
    }

Success! That access token should allow us to collect eggs on behalf of
our user account. Use ``json_decode`` on the response body to set the ``access_token``
to a variable::

    // ...
    $response = $request->send();
    $responseBody = $response->getBody(true);
    $responseArr = json_decode($responseBody, true);
    $accessToken = $responseArr['access_token'];

Using the Access Token to make API Requests
-------------------------------------------

Each grant type in OAuth represents a different strategy for getting
an access token. But no matter what grant type you use, once we have
an access token, we're dangerous! Let's use Guzzle again to make a
request to the ``/api/eggs-collect`` endpoint::

    // ...
    $accessToken = $responseArr['access_token'];

    $request = $client->post('/api/eggs-collect');
    $response = $request->send();
    echo $response->getBody(true);die("\n\n");

When we execute the script from the command line, we get an error, which
shouldn't be very surprising:

.. code-block:: json

    {
        "error": "access_denied",
        "error_description": "an access token is required"
    }

We have the ``access_token``, but we're not sending it with this new request.
Remember, the API expects us to add an ``Authorization: Bearer`` header::

    $request = $client->post('/api/eggs-collect');
    $request->addHeader('Authorization', 'Bearer '.$accessToken);
    $response = $request->send();
    echo $response->getBody(true);die("\n\n");

And just like that, it works:

.. code-block:: json

    {
        "action": "eggs-collect",
        "success": true,
        "message": "Hey look at that, 2 eggs have been collected!"
    }

If we try it again immediately, it still works:

.. code-block:: json

    {
        "action": "eggs-collect",
        "success": true,
        "message": "Hey, give the ladies a break. Makin' eggs ain't easy!"
    }

... but the hens are a little tired.

That's it! This is the Client Credentials grant type, which is a way for
us to use the Client ID and Client Secret from our application to get an
access token that can only access the account that created the application.

This is probably the simplest OAuth situation and is perfect when you need
to write something that only has access to *your* account. This is way better
than putting your username and password in the code! And because you've used
scopes to limit what your application can do, you've made things even safer!
If necessary, you can always revoke access to just the CRON job later by
deleting the application.

Ultimately, Client Credentials is *a way* to get a token that gives your
application access on behalf of a COOP user. Let's move on now to the grant
type that you're probably more familiar with: Authorization Code.

.. _`http://coop.apps.knpuniversity.com`: http://coop.apps.knpuniversity.com
