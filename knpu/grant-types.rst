Grant Types
===========

**NOTE** This *was* planned as a chapter, but I think we should just teach
    these as we show their examples. I'm leaving this file temporarily so
    we can use it as notes.

Implicit Grant Type
  - Client receives access token directly in the URL fragment
  - Pros: No extra round trip to service provider to exchange a code for a token.  Good for Javascript implementations
  - Cons: No refresh token - when the access token expires, you will need to ask the user for another token
Authorization Code
  - Client receives a Code instead of an Access Token, and makes an API call to the Service Provider to receive the token
     - Instead of you telling Hal the secret code, you give him a certificate which he can then exchange with your Friend to receive the secret code.
  - Pros: Receive an Authorization code.  More secure, as client credentials are required.  Refresh Tokens possible
  - Cons: Requires extra HTTP call to server
Resource Owner Password Credentials
  - The resource owner's password/password is used to retrieve an access token
       - You give Hal your keys.  Keys are used to obtain the "secret passcode"
  - Pros:  Good for transitioning an existing system to oauth, good for trusted 3rd party applications (i.e. internal apps)
  - Cons: Not 3-legged.  No protection from 3rd parties
Client Credentials
  - The client ID and secret are used to obtain an access token for a user linked directly to the set of credentials
      - You use your own keys to enter the house
  - Pros: Good for "service accounts" and for quick applications
  - Cons: Not 3-legged.  Limited - only have access to one user