Finishing the Login Callback
----------------------------

Finish the login callback function by copying the example from `Step 5`_ of 
the docs and tweaking the code to use jQuery:

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
in my circles and print their smiling faces:

.. code-block:: javascript

    // views/dashboard.twig
    function loadCirclesPeople() {
        var request = gapi.client.plus.people.list({
            'userId': 'me',
            'collection': 'visible'
        });
        request.execute(function (people) {
            var $people = $('#google-plus-people');
            $people.empty();
            for (var personIndex in people.items) {
                var person = people.items[personIndex];
                $people.append('<img src="' + person.image.url + '">');
            }
        });
    }

This looks for a div with the id ``google-plus-farmers``, so let's add that
to our page:

    {# views/dashboard.twig #}

    <!-- ... -->
    <a href="#" class="btn btn-lg btn-info js-google-signin">Connect with Google+</a>
    <div id="google-plus-farmers"></div>
    <!-- ... -->

Let's call this function automatically after we authenticate. This code loads the
Google+ part of the SDK and calls our function.:

.. code-block:: javascript

    function mySignInCallback(authResult) {
        if (authResult['status']['signed_in']) {
            // ...

            // loads the gapi.client.plus JavaScript object
            gapi.client.load('plus','v1', function() {
                loadCirclesPeople();
            });
        } else {
            // ...
        }
    }

Ok, let's try it! When we refresh and sign in, we get a beautiful box of
farmers in our circle! In my console, if we click on the AJAX call that was
made, we can see that an access token was sent on the ``Authorization: Bearer``
header. OAuth is happening behind the scenes!

Page-Parameters
---------------

Our ultimate goal is for the user to be able to choose from the people in
their circles and invite them to join TopCluck. With all the OAuth stuff behind
us, this is just a matter of writing some JavaScript and figuring out exactly
how to use the Google+ API to accomplish this. We'll leave this to you!

But there's one more small thing that's bothering me. When we click to sign in,
the sign-in function is called twice, which means ``loadCirclesPeople``
is called twice and 2 API requests are made to Google.

Regardless of why this happens, we could of course avoid the double-calls
by using a simple variable:

.. code-block:: javascript

        var isSignedIn = false;
        function mySignInCallback(authResult) {
            if (authResult['status']['signed_in']) {
                if (isSignedIn) {
                    return;
                }
                isSignedIn = true;

                // ...
            } else {
                // ...
            }
        }

But the reason this is happening is more interesting. Rememember how the
Facebook SDK stores the access token details in the session? The Google JavaScript
SDK stores those details in a cookie. This means that since we've already
authorized with Google+, we should *still* be signed in if we refresh. The
callback function is called twice since we were already authenticated *and* we
clicked to authenticate again.

If we already authorized during this session, we can avoid making the user
click the Connect button by moving the ``signIn`` parameters to meta tags.
This is actually what `Step 4`_ of the example does. Let's copy
these ``meta`` tags into our layout and update it with our client id. We
can also add the callback parameter here:

.. code-block:: html+jinja

    {# views/base.twig #}
    {# ... #}

    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">

    <meta name="google-signin-clientid" content="104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i.apps.googleusercontent.com" />
    <meta name="google-signin-scope" content="https://www.googleapis.com/auth/plus.login" />
    <meta name="google-signin-requestvisibleactions" content="http://schemas.google.com/AddActivity" />
    <meta name="google-signin-cookiepolicy" content="single_host_origin" />
    <meta name="google-signin-callback" content="mySignInCallback" />
    {# ... #}

Google calls this page-level configuration. One big advantage is that if
we already have an access token stored in a cookie, it will execute the callback
function on page load. Now that we have these, remove the ``params`` entirely:

.. code-block:: javascript

    // views/dashboard.twig
    $('.js-google-signin').on('click', function(e) {
        // prevent the click from going to #
        e.preventDefault();

        gapi.auth.signIn();
    });

Refresh the page. Instantly, the Sign in button disappears and our circles
show up. Whether we're managing the access token on the server or in JavaScript,
we can make it persist throughout a session. This isn't always clear, since
the Facebook and Google SDK's do a lot automatically for us. Just keep thinking
about how OAuth works and you'll be in great shape.

In this chapter, we saw how you can choose between the authorization code
or implicit grant type when starting the authorization process. And although
it has nothing to do with grant types, we also saw how the authorization
process can be done by redirecting the user, like we saw in past chapters,
*or* by opening a popup and communicating with JavaScript. Which method you'll
use will largely depend on the OAuth server and what it supports most easily.

But if you need a *pure* JavaScript solution that never touches the server,
then you need the implicit grant type. Even if you can keep much of the flow
in JavaScript, the authorization code *still* needs a server so that it can
use the client secret to exchange the code for the token.

.. _`Step 4`: https://developers.google.com/+/web/signin/javascript-flow#step_4_initiate_the_sign-in_flow_with_javascript
.. _`Step 5`: https://developers.google.com/+/web/signin/javascript-flow#step_5_handling_the_sign-in
