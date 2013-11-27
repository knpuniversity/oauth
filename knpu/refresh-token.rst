Refresh Token
=============

- Discuss token expiration and take them through what errors would happen
    if the token expires
- First, handle this situation gracefully by re-authorizing the user
- Next, introduce the idea of a refresh token. Grab this on auth, save it
    to the db
- On expiration, use it automatically to grab a new authentication token