Implicit Grant Type with Google+
================================

With Facebook integration done, Brent can use it to brag about his major egg-collecting 
success on Facebook for all his farmer friends to see .... including farmer Scott.

Now he wants to go a step further and let people invite their Google+ connections
to signup for a TopCluck account. To make it rural hipster, he wants to do this 
entirely on the frontend with JavaScript. The user will click a button to authorize 
their Google+ account, see a list of their connections, and select which ones to 
invite - all without any page reloads.

The Implicit Grant Type
-----------------------

So far we've seen 2 different grant types, or strategies for exchanging the
access token. These were Client Credentials and Authorization Code. Unfortunately,
neither works inside JavaScript. The problem is that both involve making a request 
to the OAuth server using your client secret. As the name suggests, that string is 
a secret. So, printing it inside an HTML page and using it in JavaScript would be 
a terrible idea.

Instead, we need to look at one more grant type called Implicit. It's a lot
like Authorization Code, but simpler.

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
the code. We then send this via AJAX to a page on our server, which exchanges 
it for an access token.

This approach *still* involves the server, but the work of getting the
code is delegated to JavaScript. In reality, it's just another version of
the authorization code grant type.

Client-side Flow
~~~~~~~~~~~~~~~~

Finally, let's look at the `Client-side Flow`_, which is where everything
happens in JavaScript. There are 3 variants of this type, but they're all
basically the same. When we press the "Click me" demo button, we get a popup
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
our layout:

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

Let's add a "Connect with Google+" button on the homepage and attach a jQuery
click event listener to it:

.. code-block:: html+jinja

    {# views/dashboard.twig #}

    <!-- ... -->
    <a href="#" class="btn btn-lg btn-info js-google-signin">Connect with Google+</a>
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

.. code-block:: javascript

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

`Step 5`_ of the docs show us how the function might look. Let's create our
``mySignInCallback`` function and dump the auth information.

.. code-block:: javascript

    function mySignInCallback(authResult) {
        console.log(authResult);
    }

Refresh and try it again! Awesome, we see it print out an object with an
``access_token``. This is the big difference between the Implicit flow and
the Authorization Code grant types. With Authorization Code, this step returns
a code, which we then still need to exchange for an access token by making an 
API request. But with Implicit, the access token is given to us immediately.

Choosing Authorization Code versus Implicit
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Remember that whether we're redirecting the user or using this popup method,
we can *choose* to use the Authorization Code or Implicit grant type. In
fact, the JavaScript object contains both the token *and* an authorization
code. So we can either choose to use the token in JavaScript, or do a little
more work to send the code to our server via AJAX and exchange that for a
token.

Instead of sending us both, other OAuth servers let you choose between the code
and the token.

Remember the ``response_type`` parameter we used with Coop? We set it to
``code``, which is why we got back a ``code`` query parameter on the redirect.
But we could also set it to ``token``.  And if we did, the redirect would
have contained a ``token`` parameter instead of the ``code``.

The ``response_type`` is how we tell the OAuth server which grant type we
want to use. Even Facebook has a ``response_type`` parameter on its login
URL, which has the same 2 values.

Authorization Code versus Implicit
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So why would anyone choose Authorization Code over Implicit since it has
an extra step? The big answer is security, which we'll talk about more in
the next chapter. Another disadvantage, which is also related to security,
is that the Implicit grant type can't give you a refresh token.

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
