Client Credentials
==================

Meet Brent. He's the hardworking, beard-growing, kale-munching type who
has a coop of the nicest, smartest, and best egg-laying chickens this side
o' the Mississippi! But feeding his chickens and doing other things around
the farm has always taken a lot of time.

But great news! The brand new "Chicken Oversight Operations Platform", or
COOP site has just launched! With COOP, you can login to the site and
collect your chicken eggs, unlock the barn, and do all kinds of other things
just by clicking a button.

Noticing that COOP has an API, Brent wonders if he could write a little script
that would collect his eggs automatically. Yea, if he had a script that made
an API request on his behalf, he could run it on a CRON job daily and sleep
in!

So COOP is real, sort of. You can find this make-believe website by going
to `http://coop.apps.knpuniversity.com`_. Go ahead and create an account,
and start controlling your virtual farm. It's the future!

Starting our Command-line Script
--------------------------------

COOP's API is simple, with just a few endpoints, including the one we
want for our little command-line script: eggs-collect.

I've already made a ``cron/`` directory with a script called ``collect_eggs.php``
that'll get us started::

    // collect_eggs.php
    include __DIR__.'/vendor/autoload.php';
    use Guzzle\Http\Client;

    // create our http client (Guzzle)
    $http = new Client('http://coop.apps.knpuniversity.com', array(
        'request.options' => array(
            'exceptions' => false,
        )
    ));

.. tip::

    Code along with us! Click the Download button on this page to get the starting
    point of the project, and follow the readme to get things setup.

It doesn't do anything except create a ``Client`` object that's pointing
at the COOP website. Since we'll need to make HTTP requests to the COOP API,
we'll use a really nice PHP library called `Guzzle`_. Don't worry if you've
never used it, it's really easy.

Before we start, we need to use `Composer`_ to download Guzzle. `Download Composer`_
into the ``cron/`` directory and then install the vendor libraries:

.. code-block:: bash

    php composer.phar install

.. note::

    New to Composer? Do yourself a favor and master it for free:
    `The Wonderful World of Composer`_. 

Let's try making our first API request to ``/api/2/eggs-collect``. The ``2``
is our COOP user ID, since we want to collect eggs from *our* farm. Your
number will be different::

    // collect_eggs.php
    // ...

    $request = $http->post('/api/2/eggs-collect');
    $response = $request->send();
    echo $response->getBody();

    echo "\n\n";

Try it by executing the script from the command line:

.. code-block:: bash

    php collect_eggs.php

Not surprisingly, this blows up!

.. code-block:: json

    {
      "error": "access_denied",
      "error_description": "an access token is required"
    }

OAuth Applications
------------------

But before we think about getting a token, we need to create an application
on COOP. The application represents the external app or website that we want
to build. In our case, it's the little command-line script. In OAuth-speak,
it's this application that will actually ask for access to a user's COOP account.

Give it a name like "Brent's Lazy CRON Job", a description, and check only
the box for "Collect Eggs from Your Chickens". These are "scopes", or basically
the permissions that your app will have if a token is granted from COOP.

When we finish, we now have a Client ID and an auto-generated "Client Secret".
These are a sort of username and password for the application. One tricky
thing is that the terms "application" and "client" are used interchangeably
in OAuth. And both are used to refer to the application we just registered
and the actual app you're building, like the CRON script or your website.
I'll try to clarify along the way.

Now, let's get an access token!

Client Credentials Grant Type
-----------------------------

The first OAuth grant type is called Client Credentials, which is the simplest
of all the types. It involves only two parties, the client and the server.
For us, this is our command-line script and the COOP API.

Using this grant type, there is no "user", and the access token we get will
only let us access resources under the control of the application. When we
make API requests using this access token, it's almost like we're logging in 
as the *application* itself, not any individual user. I'll explain more in a second.

If you visit the application you created earlier, you'll see a nice
"Generate a Token" link that when clicked will fetch one. Behind the scenes,
this uses client credentials, which we'll see more closely in a second.

.. code-block:: text

    http://coop.apps.knpuniversity.com/token
        ?client_id=Your+Client+Name
        &client_secret=abcdefg
        &grant_type=client_credentials

But for now, we can celebrate by using this token immediately to take actions
on behalf of the application!

Access Tokens in the API
------------------------

Exactly how to do this depends on the API you're making requests to. One common method,
and the one COOP uses, is to send it via an Authorization Bearer header.

.. code-block:: text

    GET /api/barn-unlock HTTP/1.1
    Host: coop.apps.knpuniversity.com
    Authorization: Bearer ACCESSTOKENHERE

Update the script to send this header::

    // collect-eggs.php
    // ...

    $accessToken = 'abcd1234def67890';

    $request = $http->post('/api/2/eggs-collect');
    $request->addHeader('Authorization', 'Bearer '.$accessToken);
    $response = $request->send();
    echo $response->getBody();

    echo "\n\n";

When we run the script again, start celebrating, because it works!
And now we have enough eggs to make an omlette :)

.. code-block:: json

    {
      "action": "eggs-collect",
      "success": true,
      "message": "Hey look at that, 3 eggs have been collected!",
      "data": 3
    }

Trying to Collect Someone Else's Eggs
-------------------------------------

Notice that this collects the eggs for *our* user becase we're including
our user ID in the URL. What happens if we change id to be for a different user?

    /api/3/eggs-collect

If you try it, it fails!

.. code-block:: json

    {
      "error": "access_denied",
      "error_description": "You do not have access to take this action"
    }

Technically, with a token from client credentials, we're making API requests
not on behalf of a user, but on behalf of an application. This makes client
credentials perfect for making API calls that edit or get information about
the application itself, like a count of how many users it has.

We decided to build COOP so that the application *also* has access to modify
the user that created the application. That's why we *are* able to collect our user's
eggs, but not our neighbor's.

Getting the Token via Client Credentials
----------------------------------------

Put the champagne away: we're not done yet. Typically, access tokens don't
last forever. COOP tokens last for 24 hours, which means that tomorrow, our
script will break.

Letting the website do the client-credentials work for us was nice for testing,
but we need to do it ourselves inside the script. Every OAuth server has an
API endpoint used to request access tokens. If we look at the COOP API Authentication
docs, we can see the URL and the POST parameters it needs:

    http://coop.apps.knpuniversity.com/token
    
    Parameters:
        client_id
        client_secret
        grant_type

Let's update our script to first make *this* API request. Fill in the ``client_id``,
``client_secret`` and ``grant_type`` POST parameters::

    // collect-eggs.php
    // ...

    // run this code *before* requesting the eggs-collect endpoint
    $request = $http->post('/token', null, array(
        'client_id'     => 'Brent\'s Lazy CRON Job',
        'client_secret' => 'a2e7f02def711095f83f2fb04ecbc0d3',
        'grant_type'    => 'client_credentials',
    ));

    // make a request to the token url
    $response = $request->send();
    $responseBody = $response->getBody(true);
    var_dump($responseBody);die;
    // ...

With any luck, when you run it, you should see a JSON response with an access
token and a few other details:

.. code-block:: json

    {
      "access_token": "fa3b4e29d8df9900816547b8e53f87034893d84c",
      "expires_in": 86400,
      "token_type": "Bearer",
      "scope": "chickens-feed"
    }

Let's use *this* access token instead of the one we pasted in there::

    // collect-eggs.php
    // ...

    // step1: request an access token
    $request = $http->post('/token', null, array(
        'client_id'     => 'Brent\'s Lazy CRON Job',
        'client_secret' => 'a2e7f02def711095f83f2fb04ecbc0d3',
        'grant_type'    => 'client_credentials',
    ));

    // make a request to the token url
    $response = $request->send();
    $responseBody = $response->getBody(true);
    $responseArr = json_decode($responseBody, true);
    $accessToken = $responseArr['access_token'];

    // step2: use the token to make an API request
    $request = $http->post('/api/2/eggs-collect');
    $request->addHeader('Authorization', 'Bearer '.$accessToken);
    $response = $request->send();
    echo $response->getBody();

    echo "\n\n";

Now, it still works *and* since we're getting a fresh token each time, we'll
never have an expiration problem. Once Brent sets up a CRON job to run our
script, he'll be sleeping in 'til noon!

Why, What and When: Client Credentials
--------------------------------------

Every grant type eventually uses the ``/token`` endpoint to get a token, but
the details before that differ. Client Credentials is *a way* to get a token
directly. One limitation is that it requires your client secret, which is
ok now because our script is hidden away on some server.

But on the web, we won't be able to expose the client secret. And that's
where the next two grant types become important.

.. _`Guzzle`: http://guzzlephp.org/
.. _`Composer`: http://getcomposer.org/
.. _`Download Composer`: http://getcomposer.org/download/
.. _`http://coop.apps.knpuniversity.com`: http://coop.apps.knpuniversity.com
.. _`Download Composer`: http://getcomposer.org/download/
.. _`The Wonderful World of Composer`: http://knpuniversity.com/screencast/composer
