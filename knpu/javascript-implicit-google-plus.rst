Implicit Grant Type with Google+
================================

With Facebook integration done, Brent's super happy because he can post his
major egg-collecting success on Facebook for all his farmer friends to see.

Now he wants to go a step further and let people invite their Google+ connections
to signup for a TopCluck account and get in on the fun. To make it really
hipster, he wants to do this entirely on the frontend with JavaScript. The
user will click a button to authorize their Google+ account, see a list of
their connections, and select which ones to invite - all without any page
reloads.

The Implicit Grant Type
-----------------------

So far we've seen 2 different grant types, or strategies for exchanging the
access token. These were Client Credentials and Authorization Code. Unfortunately,
neither works inside JavaScript. The problem is that both ultimately involve
making a request to the OAuth server using your
client secret. As the name suggests, that string is a secret. So, printing
it inside an HTML page and using it in JavaScript would be a terrible idea.

Instead, we need to look at one more grant type called Implicit. It's a lot
like Authorization Code, but simpler. With Authorization Code, the user is
redirected back to our app with an authorization code. We make another API
request to the token endpoint to exchange it for an access token.

With the Implicit flow, when the user is sent back to us, they come with
the access token directly - instead of the authorization code. This eliminates
one step of the process. But it also has some disadvantages as we'll see.

JavaScript OAuth with Google+
-----------------------------

To integrate with Google+, let's start by finding their `JavaScript Quick Start`_,
which is a little example app. If we follow the `Google+ Sign-In button`_,
we can get some actual details on how Google+ sign in works.

Now that we know a lot about OAuth, the "Choosing a sign-in flow" is really
interesting. This is a great example of how the OAuth grant types will
look slightly different depending on the server.

Pure server-side flow
~~~~~~~~~~~~~~~~~~~~~

First, look at the `Pure server-side flow`_. If you look closely, the steps
are describing the authorization code grant type. The redirect is done via
JavaScript, but with all the familiar parameters like ``scope``, ``response_type``
and ``client_id``. After the redirect, the server checks for a ``code`` query
parameter and uses a Google PHP SDK to get an access token.

Hybrid server-side flow
~~~~~~~~~~~~~~~~~~~~~~~

Next, go back and look at the `Hybrid server-side flow`_. This is another
version of the authorization code grant type, which has 2 major differences.

First, instead of redirecting the user, we use a little Google+ JavaScript
library and some markup. When the user clicks the sign in link, it doesn't
redirect the user. Instead, it opens a popup, which asks the user to authorize
your app.

The second big difference is how we get the authorization code. After the
user authorizes our application, the popup closes. Instead of redirecting
the user to a URL on our site, a JavaScript function is called and passed
the authorization code. We then send this via AJAX to a page on our server,
which exchanges it for an access token.

This approach *still* involves the server, but the work of getting the authorization
code is delegated to JavaScript. In reality, it's just another version of
the authorization code grant type.

Client-side Flow
~~~~~~~~~~~~~~~~

Finally, let's look at the `Client-side Flow`, which is where everything
happens in JavaScript. There are 3 variants of this type, but they're all
basically the same. When we click "Click me" demo button, we get a popup
asking for authorization. And immediately after approving, some JavaScript
on the page shows us the ``access_token`` and some other details. This happens
completely without the server.

Creating the Google Application
-------------------------------

Like everything, our first step is to create an application so that we have
a client ID and client secret. Click to go to the `Developers Console`_ and
create a new project.

Next, click `APIs and auth` and make sure the "Google+ API" is set to ON.

Finally, click "Credentials" on the left and click the "Create New Client ID"
button. Keep "Web Application" selected and fill in your domain name. Since
we won't be using the Authorization Code grant type and redirecting the user,
we only really need to worry about the JavaScript origins. Google makes us
fill these in for security purposes - a topic we'll cover later.

When we're finished, we have a brand new Client ID and secret. Keep these handy!

Including the JavaScript SDK
----------------------------

The implicit OAuth flow can be done without any tools, but Google makes our
life a lot easier by giving us a JavaScript SDK. `Copy the script`_ into
our layout::

.. code-block:: html+jinja

    {# views/base.twig #}

    <script src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
    <script src="{{ app.request.basePath }}/js/bootstrap.min.js"></script>

    <script type="text/javascript">
        (function () {
            var po = document.createElement('script');
            po.type = 'text/javascript';
            po.async = true;
            po.src = 'https://apis.google.com/js/client:plusone.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(po, s);
        })();
    </script>

    {# ... #}

This exposes a global ``gapi`` object we'll use in a second.

Initiate the Sign-in Flow
-------------------------

Let's add a "Sign in with Google" button on the homepage and attach a jQuery
click event listener to it:

.. code-block:: html+jinja

    {# views/dashboard.twig #}

    <!-- ... -->
    <a href="#" class="btn btn-lg btn-info js-google-signin">Sign in with Google+</a>
    <!-- ... -->

    {% block javascripts %}
        {{ parent() }}

        <script>
            jQuery(document).ready(function() {
                $('.js-google-signin').on('click', function(e) {
                    // prevent the click from going to #
                    e.preventDefault();
                });
            });
        </script>
        {# Put any JavaScript here #}
    {% endblock %}

We can start the authentication process by using the ``signIn`` method of
the ``gapi.authentication`` JavaScript object:

.. code-block:: javascript

    jQuery(document).ready(function() {
        $('.js-google-signin').on('click', function(e) {
            // prevent the click from going to #
            e.preventDefault();

            gapi.auth.signIn();
        });
    });

When we try it, nothing happens. In fact, there's a JavaScript error:

.. code-block:: text

    cookiepolicy is a required field.  See
    https://developers.google.com/+/web/signin/#button_attr_cookiepolicy
    for more information.

What we're trying to do here is *similar* to the step in the Authorization
Code grant type where we originally redirect the user to the OAuth server.
There are details we need to send to Google+, like our client id and the
scopes we want.

In fact, the ``gapi.auth`` object has `nice documentation`_ and the ``signIn``
method there shows us the common parameters we need:

.. code-block:: javascript

    // just the example copied from https://developers.google.com/+/web/api/javascript#gapiauthsigninparameters
    function initiateSignIn() {
      var myParams = {
        'clientid' : 'xxxxxxxxxxxxxx..apps.googleusercontent.com',
        'cookiepolicy' : 'single_host_origin',
        'callback' : 'mySignInCallback',
        'scope' : 'https://www.googleapis.com/auth/plus.login',
        'requestvisibleactions' : 'http://schemas.google.com/AddActivity'
        // Additional parameters
      };
      gapi.auth.signIn(myParams);
    }

Let's copy these into our JavaScript. Update the ``clientid`` but keep the
``scope`` as it will let us access the user's social graph. The ``requestvisibleactions``
parameter relates to posting activities - you can leave it, but we won't
need to worry about it:

.. code-block:: javascript::

        jQuery(document).ready(function() {
            $('.js-google-signin').on('click', function(e) {
                // prevent the click from going to #
                e.preventDefault();

                var myParams = {
                    'clientid': '104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i.apps.googleusercontent.com',
                    'cookiepolicy': 'single_host_origin',
                    'callback': 'mySignInCallback',
                    'scope': 'https://www.googleapis.com/auth/plus.login',
                    'requestvisibleactions': 'http://schemas.google.com/AddActivity'
                };
                gapi.auth.signIn(myParams);
            });
        });

The ``cookiepolicy`` tells the SDK to set cookie data that's only accessible
by our host name. This is a necessary detail just to make sure the data being
passed around can't be read by anyone else.

All of these parameters are explained nicely on the `documentation page`_.

Let's try it again! Now we get the popup which asks us to authorize the app.
And when we approve, we get a JavaScript error:

.. code-block:: text

    Callback function named "mySignInCallback" not found

That's actually great! Instead of redirecting the user back to a URL on our
site, Google passes us the OAuth details by calling a JavaScript function.
Calling the JavaScript function here serves the same purpose as a browser
redirect: it hands off authorization data from the server to the client.
This isn't special to the Implicit flow - the `Hybrid server-side flow`_
we looked at earlier is an example of an Authorization Code grant type that
does this part in JavaScript as well.

Now we just need to write this function. If we look at `Step 5`_, we can
see how this function should work. It's passed an ``authResult`` variable
that contains authentication information.

Let's create the ``mySignInCallback`` function and just prints these details:

.. code-block:: javascript

    function mySignInCallback(authResult) {
        console.log(authResult);
    }

Refresh and try it again! Awesome, we see it print out an object with an
``access_token``. This is the big difference between the Implicit flow and
the Authorization Code grant types. With Authorization Code, this step returns
an authorization code, which we then still need to exchange for an access
token by making an API request. But with Implicit, the access token is given
to us immediately.

Choosing Authorization Code versus Implicit
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Remember that whether we're redirecting the user or using this popup method,
we can *choose* to use the Authorization Code or Implicit grant type. So
then, when and how did we tell the Google OAuth server that we wanted to use
the implicit flow? Why isn't it giving us an authorization code here instead?

The answer for Google+ is a parameter called ``redirecturi``. Set this to
``postmessage`` and try again:

.. code-block:: javascript

    var myParams = {
        'clientid': '104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i.apps.googleusercontent.com',
        'cookiepolicy': 'single_host_origin',
        'callback': 'mySignInCallback',
        'scope': 'https://www.googleapis.com/auth/plus.login',
        'requestvisibleactions': 'http://schemas.google.com/AddActivity',
        // add this temporarily!
        'redirecturi': 'postmessage'
    };
    gapi.auth.signIn(myParams);

This time, the ``authResult`` includes a ``code`` and *not* an ``access_token``.
This is the authorization code grant type inside JavaScript. We would *still*
need to AJAX this value back to the server so that it could exchange the
authorization code for an access token. That can't be done from inside JavaScript
since it requires the client secret, which we need to keep hidden away on
the server.

Setting the ``redirecturi`` to ``postmessage`` in order to get the authorization
code grant type is special to the Google+ OAuth server. However, when we
start the authorization process - whether we're redirecting the user or opening
up a popup - all OAuth servers have a way for us to tell it that we want
a code returned or the access token.

Remember the ``response_type`` parameter we used with Coop? We set it to
``code``, but we could also set it to ``token``. If we did that, the redirect
would have contained the access token instead of the authorization code.
Even Facebook has a ``response_type`` parameter on its login URL, which has
the same 2 values.

Authorization Code versus Implicit
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So why would anyone choose Authorization Code over Implicit since it has
an extra step? The big answer is security, which we'll talk about more in
the next chapter. Another disadvantage, which is also related to security,
is that the Implicit grant type can't give you a refresh token.

Finishing the Login Callback
----------------------------

Remove the ``redirecturi`` parameter and finish the login callback function
by copying the examle from `Step 5`_ of the docs and making some changes:

.. code-block:: html+jinja

    function mySignInCallback(authResult) {
        if (authResult['status']['signed_in']) {
            // Update the app to reflect a signed in user
            $('.js-google-signin').hide();
        } else {
            // Possible error values:
            //   "user_signed_out" - User is signed-out
            //   "access_denied" - User denied access to your app
            //   "immediate_failed" - Could not automatically log in the user
            console.log('Sign-in state: ' + authResult['error']);
        }
    }

When we refresh and try again, the sign in button disappears, proving that
authentication was successful!

Using the API
-------------

Just like with the Facebook PHP SDK, the Google JavaScript SDK now has an
access token that it's storing. This means we can start making API calls.
I'll copy in a function that uses the API to get a list of all of the people
in my circles and

.. _`JavaScript Quick Start`: https://developers.google.com/+/quickstart/javascript
.. _`Google+ Sign-In button`: https://developers.google.com/+/web/signin/
.. _`Pure server-side flow`: https://developers.google.com/+/web/signin/server-side-flow
.. _`Hybrid server-side flow`: https://developers.google.com/+/web/signin/server-side-flow
.. _`Client-side Flow`: https://developers.google.com/+/web/signin/javascript-flow
.. _`Developers Console`: https://cloud.google.com/console/project
.. _`Copy the script`: https://developers.google.com/+/web/signin/javascript-flow#step_2_include_the_google_script_on_your_page
.. _`nice documentation`: https://developers.google.com/+/web/api/javascript
.. _`documentation page`: https://developers.google.com/+/web/api/javascript
.. _`Step 5`: https://developers.google.com/+/web/signin/javascript-flow#step_5_handling_the_sign-in

-- how are the client-side API requests being made behind the scenes?
-- how does Facebook's JavaScript implementation differ and how does this
    relate (or what should we mention about) the code versus token response
    type when doing the authorization redirect.
-- mention no refresh token
-- token should be validated? (https://developers.google.com/accounts/docs/OAuth2?csw=1#scenarios)
-- page-parameters


Client ID   104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i.apps.googleusercontent.com
Email address   104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i@developer.gserviceaccount.com
Client secret   GC3rBLT2Sv7zh2PTFx7-XP5t
Redirect URIs
https://localhost:9000/oauth2callback
Javascript Origins
https://localhost:9000
