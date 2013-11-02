Development
===========

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

## Create your Application
    - Enter “Hal’s Housing Help” for "Application Name"
    - Leave “Description” blank
    - Leave “Redirect URI” blank
    - Click “Unlock Door” for the “scope” (this is what we want to be able to do)
    - Press submit.  You've now registered Hal!
    - Copy the secret that has been generated for your client, you will need this in the next step

# The OAuth Client (Basic)

0. Configure the Client App
    - [NOTE to KNP] - consider getting rid of this, and having the user ENTER these options EVERY TIME
        - this will reinforce what they are, where they are used, and where they come from
    - Open up `client-start\data\parameters.ini`
    - set the newly-generated `client_secret` as your client secret
        - Make note of the other parameters, which are configurable
        - These settings are required for any oauth integration
1. Run the Client App
    - instructions in client-start\README.md
    - configure in apache, or your web server of choice
    - Open up in your browser
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
6. Request the Access Token
    - Open up `client-start\src\OAuth2Demo\Client\Controllers\RequestToken`
    - Add the following under the parameter declarations to create the parameters you will use in the request body:
        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri
        ];
    - Add the call with Guzzle to the oauth API
        $response = $http->post('http://localhost:9000/token', null, $params, $config['http_options'])->send();
        $json = json_decode((string) $response->getBody(), true);
    - Uncomment the lines to display the response
    - Open Up `client-start\views\show_authorization_code.twig`
    - Uncomment the "Make a token Request" button, and comment out the "Exchange Access Token" button
        - <a class="button" href="{{ path('request_token_with_authcode', { 'code': code }) }}">make a token request</a>
    - Go through the workflow again.  Your token will be displayed in the UI.
7. Use the Access Token
    - Copy the access token and click "Unlock the House's Door"
    - Enter the Access Token and click "Submit"
    - See the successful API call!
        - Try changing the access token, and see the error returned
        - Note the only thing required is the token
            - the ClientId/Secret has already been used to obtain it, so they are not necessary in subsequent calls

8. Automate the Access Token
    - Open up `client-start\src\OAuth2Demo\Client\Controllers\RequestResource`
    - Add the line to create the Authorize header in your request:
        - $headers =  array('Authorization' => sprintf('Bearer %s', $token));
        - Note: This can be done in a querystring or request body as well
    - Add the line to call the Server's APIs with guzzle:
        $response = $http->post($endpoint, $headers, $config['http_options'])->send();
        $json = json_decode((string) $response->getBody(), true);
    - Uncomment the lines to display the response
    - Open Up `client-start\views\show_access_token.twig`
    - Comment out the line "Once this is done, use the token to Unlock the House's Door!"
    - Uncomment the lines to "make a token request"
    - Go through the workflow again, and see your API request displayed in the browser!