Security
========

How to make your OAuth APIs the most secure:
     - Require redirect URI exact match
     - Require "state" parameter - prevents man-in-middle
     - Only allow Authorization Code grant type
     - TODO: see http://homakov.blogspot.com/2012/08/saferweb-oauth2a-or-lets-just-fix-it.html
     - http://www.thread-safe.com/2012/01/problem-with-oauth-for-authentication.html