Authorization Code
==================

Suddenly, Brent is jolted awake at noon to the sound of farmer Scott driving
his eggs to the market and screaming "Haha, Brent! My chickens lay way more
eggs than yours!" But in reality, Brent *knows* that his chickens are way
better egg-making hens than Scott's... but how to prove it?

  *show Brent's and his chickens facing off against Scott and his chickens*

Then it hits him! The COOP API has an endpoint to see how many eggs have
been collected from a user's farm each day. Brent decides to create a new
website that will use this endpoint to count how many total eggs a COOP user's
farm has collected. He'll call it: Fantasy Chicken League, or FCL for short.
But in order to be able to call the ``/api/eggs-count`` endpoint on behalf of
each user, the site will use OAuth to collect an access token for every
farmer that signs up.

Once again, the question is: how can each user give FCL an access token that
allows it to count the user's eggs on the user's behalf?

- download the starting app
- register, login,
- take (or fail to take) some sort of action on behalf of the user?
- add a note about how less scopes in your app is better