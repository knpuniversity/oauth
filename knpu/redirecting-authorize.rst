Redirecting and Authorizing your Site
=====================================

- Setting up the URL to redirect the user
    - redirect URL
    - scope
    - app id?
- Create an application on HouseRobot
- redirect the user
- catch on the way back, look at the authorization token
- what do we do with this? We need to exchange it and quickly!

## Create your Application
    - Enter “Hal’s Housing Help” for "Application Name"
    - Leave “Description” blank
    - Leave “Redirect URI” blank
    - Click “Unlock Door” for the “scope” (this is what we want to be able to do)
    - Press submit.  You've now registered Hal!
    - Copy the secret that has been generated for your client, you will need this in the next step

2. Add the Guzzle Client
    - We will be using [Guzzle](guzzlephp.org) to make http requests to the oauth server
        - there is no proprietary code required
    - Open up `client-start\src\OAuth2Demo\Client\Client.php`
        - This is our app configuration.  notice the use of the container, session, and routes
    - Add Guzzle to the container
        - `$app['http_client'] = new GuzzleClient();`
3. Add the "Authorize Call"
    - We need a link for our homeowner so he/she can approve our request to unlock the door
    - Open up `client-start\src\OAuth2Demo\Client\Controllers\Homepage`
    - Note the configuration being pulled from the container
    - Open up `client-start\views\index.twig`
        - it is here we want to add a link or button to the Server for authorization
    - add the following code in the `content` block:
        - <a class="button" href="{{ authorize_url }}?response_type=code&client_id={{client_id}}&redirect_uri={{ redirect_uri }}&scope={{ scope }}&state={{session_id}}">Authorize</a>
    - Go over all parameters being passed in (show table with term/definition, maybe pictures if depth is required)
        - response_type
        - client_id
        - scope
        - state
    - Click the link!
4. Grant the Authorization Code
    - Notice as soon as we click the "authorize" link, we are now assuming the role of the "homeowner", or end user
    - Notice the application requesting access (Hal's Housing Help) is shown as the requesting party
    - Notice the action being requested (or "scope") is also displayed
        - Question: What happens if no scope is supplied / requested?
    - Click "Yes I authorize this request"
5. Receive the Authorization Code
    - [NOTE to KNP] The client-start app will ONLY feature the "Exchange Access Token" button, but the client-finish app will have the "make a token request" button
    - Click "Exchange Access Token"
    - Enter your information manually
    - Enter "http://knp-oauth-client/receive_authcode" in the redirect URI, as this was supplied with your "authorize" link
        - [NOTE to KNP]: is it worth explaining the nuances of the redirect_uri here?  Or should we just gloss over it? i.e. You can NOT supply the redirect_uri as long as one (and only one) is registered, but this is less secure, and should not really be used.  I think we can skip it
    - Click "Submit"
    - If everything goes accordingly, your authorization code will be EXPIRED!!! You took too long.
    - Okay, let's go back and do it with the APIs