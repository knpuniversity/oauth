Client Credentials
==================

Chapter on using client-credentials to create a single-line script that you
run via a CRON job to collect your eggs.

The Client Credentials Flow
---------------------------

OAuth has a few different variations, called grant types. A grant type is
basically a strategy for *how* we get the all-important access token that
lets us make API requests on behalf of some user. Each grant type has different
use-cases as we'll see.

- go on to think of a good use-case for showing client credentials. This
    is nice because it's simple: just get the access token. Then, show
    how we could go immediately to our client app and start using it.
