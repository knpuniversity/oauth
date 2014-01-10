Getting the Access Token
========================

TODO - this will be replaced by authorization-code.rst

- Create a new page that matches our redirectURI
- make a request out to the HouseRobot
- get back the access token
- did we take too long?
- store it in the session
- store it somewhere so we can see if we have one
- use the access token! Yeehaw!

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
