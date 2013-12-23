Client Credentials
==================

Brent needs mo' sleeps, so his first goal is to create a single-file PHP
script that will use the COOP API to collect his eggs for him. With a CRON
job, this will let him sleep in every morning.

To use the COOP API, we just need an access token. In OAuth, there are multiple
ways to get the access token, based on your situation. These are called grant
types, and we'll talk about the 3 most important ones. For our CRON script,
we'll use one called Client Credentials. The Client Credentials grant type is
the simplest of all the grant types. Because it only involves the client and
the server, it is perfect for routine service calls like the one in this
example. This may not *feel* like the OAuth flow you're used to, but we'll get
there.

OAuth Applications
------------------

First, go back to COOP and create an application. You'll make an
application for each external app or script that you write to interact with the
COOP API. Let's give it a name, a description, and check only the box
for "Collect Eggs from Your Chickens". These checkboxes are called "scopes",
which are basically permissions. In this case, we're creating an application
that will only have access to collect eggs on someone's account.

When we finish, we now have a Client ID, which we created, and an auto-generated
"Client Secret". You can think of these sort of like the username and password
for this application.

In the Client Credentials grant type, we simply want to use the application
to get an access token that gives us access to collect eggs on *our* account.
Every OAuth server has an endpoint where you can retrieve an access token.
In fact, COOP gives us a nice little link to retrieve an access token.

    Click the "Get an Access Token!" link on your application page

On the next page, we see a token that can be used immediately to take actions
on *our* account via the API. In fact, let's copy it, go to the sandbox for
collecting eggs and enter it into the form. And just like that, COOP collects
our eggs!

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

This is the all-important endpoint for retrieving tokens, and there are 3
important pieces:

#. The ``grant_type`` says that we're using the client_credentials grant
   type. This says that we want to simply access *our* account: the account
   associated with the application. Later, we'll use other grant types to
   access other people's accounts.

#. The ``client_id`` and ``client_secret`` parameters identify and authenticate
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

Send the request with the ``send()`` method, set
its return value to a ``$response`` variable, and print its body so we can
see what we get back::

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

Now, we can use ``json_decode`` on the response body to set the ``access_token``
to a variable::

    // ...
    $response = $request->send();
    $responseBody = $response->getBody(true);
    $responseArr = json_decode($responseBody, true);
    $accessToken = $responseArr['access_token'];

Using the Access Token to make API Requests
-------------------------------------------

With the access token, we're dangerous! Let's use Guzzle again to make a
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

Ok, our script is done! This is the Client Credentials grant type, which
is a way for us to use the Client ID and Client Secret from our application
to get an access token that can only access the account that created
the application.

This is probably the simplest OAuth situation and is perfect when you need
to write something that only has access to *your* account. This is way better
than putting your username and password in the code! And because you've used
scopes to limit what your application can do, you've made things even safer!
If necessary, you can always revoke access to just the CRON job later by
deleting the app.

Ultimately, Client Credentials is *a way* to get a token that gives your
application access on behalf of a COOP user. Let's move on now to the grant
type that you're probably more familiar with: Authorization Code.
