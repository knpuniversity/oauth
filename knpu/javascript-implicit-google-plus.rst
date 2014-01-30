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
access token. These were client credentials and authorization code. Unfortunately,
neither works inside JavaScript. The problem is that both ultimately involve
making a request to the token endpoint (e.g. ``/token``) and including your
client secret. As the name suggests, that string is a secret. So, printing
it inside an HTML page and using it in JavaScript would be a terrible idea.

Instead, we need to look at one more grant type called implicit. It's a lot
like authorization code, but simpler. With authorization code, the user is
redirected back to our app with an authorization code. We make another API
request to the token endpoint to exchange it for an access token.

With the implicit flow, when the user is sent back to us, they come with
the access token directly - instead of the authorization code. This eliminates
one step of the process. But it also has some disadvantages as we'll see.

JavaScript OAuth with Google+
-----------------------------

To integrate with Google+, let's start by finding their `JavaScript Quick Start`_,
which is a little example app. If we follow the `Google+ Sign-In button`_,
we can get some actual details on how Google+ sign in works.

Now that we know a lot about OAuth, the "Choosing a sign-in flow" is really
interesting. These are a great example of how the OAuth grant types will
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
we won't be using the authorization code grant type and redirecting the user,
we only really need to worry about the JavaScript origins. Google makes us
fill these in for security purposes - a topic we'll cover later.

When we're finished, we have a brand new Client ID and secret. Keep these handy.


Client ID	104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i.apps.googleusercontent.com
Email address	104029852624-a72k7hnbrrqo02j5ofre9tel76ui172i@developer.gserviceaccount.com
Client secret	GC3rBLT2Sv7zh2PTFx7-XP5t
Redirect URIs	
https://localhost:9000/oauth2callback
Javascript Origins	
https://localhost:9000


.. _`JavaScript Quick Start`: https://developers.google.com/+/quickstart/javascript
.. _`Google+ Sign-In button`: https://developers.google.com/+/web/signin/
.. _`Pure server-side flow`: https://developers.google.com/+/web/signin/server-side-flow
.. _`Hybrid server-side flow`: https://developers.google.com/+/web/signin/server-side-flow
.. _`Client-side Flow`: https://developers.google.com/+/web/signin/javascript-flow
.. _`Developers Console`: https://cloud.google.com/console/project

-- how are the client-side API requests being made behind the scenes?
-- how does Facebook's JavaScript implementation differ and how does this
    relate (or what should we mention about) the code versus token response
    type when doing the authorization redirect.
-- mention no refresh token
-- token should be validated? (https://developers.google.com/accounts/docs/OAuth2?csw=1#scenarios)