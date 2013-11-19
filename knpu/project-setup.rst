Project Setup and the Client Credentials Grant Type
===================================================

- get the server running
- show us registering on the server for our account and controlling things
    in the site. Yay!
- check out the server's API and grab a client manually?
    -> client credentials flow!?
- Show the client. Show how there are buttons *ready* to make requests to
    the server on your behalf, but that they don't work yet :/ - we don't
    have a key!

- go manually grab your key and add it to your account. Everything works!


- talk about how this flow could be easier for the user, introduce the
    whole flow - from redirecting, access token, authorization token, etc


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

