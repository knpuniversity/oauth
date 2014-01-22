Single Signon
=============

- check if user is logged in and create the user if they are not
- blank password
- what if the coopUserId exists or the email exists?
- log the user in
- add the login link


At this point, we really have everything we need to get secure, temporary
access to exactly the pieces of someone's HouseRobot account that we need.
Of course, we're not actually storing the access token anywhere, so it's
not really possible yet to use it later on to feed the chickens. Either Brent
is on the site right now and we have an access token stored in his session,
of we don't have anything.

To fix this, let's create a user account system on our site. We *could* make
a registration form. Or instead, at the moment we get the authentication
token for a user, we could create a user account for them right then. This
is exactly what's happening when you sign into a site using Facebook.

- after getting auth token, create a new user record, save the auth token
- also make an API request back to HouseRobot to get some basic user details,
    like email. Check to see if the user exists first
- Add logout support
- Show how we can logout, then log back in
- make some API requests
- note how we could have *not* done the single sign-on, but we'd still want
    to store the token in the session

