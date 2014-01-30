Using Refresh Tokens
====================

Brent has a big problem. A user can already log in and click a link that
uses the COOP API to count the number of eggs collected that day. But that's
manual, and if a farmer forgets, his egg count will show up as zero.

Intead, he wants to write a CRON JOB that automatically counts the eggs
for every user each day. The problem is that each COOP access token expires
after 24 hours. And since we can't redirect and re-authorize the user from
a CRON job, when a token expires, we can't count eggs.

Refresh Tokens
--------------

Fortunately, OAuth comes with an awesome idea called refresh tokens. If you
have a refresh token, you can use it to get a fresh access token.

Open up the CoopOAuthController where we make the API request to ``/token``.
Let's dump this response and go through the process.


- Discuss token expiration and take them through what errors would happen
    if the token expires
- First, handle this situation gracefully by re-authorizing the user
- Next, introduce the idea of a refresh token. Grab this on auth, save it
    to the db
- On expiration, use it automatically to grab a new authentication token
- added benefit: no re-authorize
- why do refresh tokens exist? Why would that be more secure than an access
    token with a forever expiration?
- Do refresh tokens always last forever? When will they expire?
