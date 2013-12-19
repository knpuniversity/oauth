Life On the Farm
================

Meet Brent. He's the hardworking, beard-growing, organic farming type who
has a coup of the nicest, smarter, and best egg-laying chickens on this side
of the Mississippi! But feeding his chickens and doing other thing around
the farm have always taken a lot of time.

  *show Brent being a hero on his farm*

But great news! The brand new "Chicken Oversight Operations Platform", or
COOP site has just launched! With COOP, you can just login to the site and
collect your chicken eggs, unlock the barn, and do all kinds of other things
just by clicking a button.

  *show Brent on a computer, and some big robot collecting chicken eggs*

With all his free time, Brent also learns how to program and starts learning
about COOP's awesome API, which uses OAuth 2.0. And as easy as it is now
to collect his eggs, he wonders if he could write a little script that would
do it automatically. Yea, if we had a script that made an API request on his
behalf, he could run it on a CRON job daily and sleep in!

  *show Brent having a Eureka moment or imagining himself sleeping while his computer runs a CRON*

Suddenly, Brent is jolted from his dreaming to the sound of farmer Scott driving
his eggs to the market and screaming "Haha, Brent! My chickens lay way more
eggs than yours!" But in reality, Brent *knows* that his chickens are way
better egg-making hens than Scott could ever hope for! But how to prove it?

Then it hits him! Fantasy Chicken League: a new website that Brent will build
that will keep track of *exactly* how many chickens come from each farm!
The COOP API defines an endpoint that allows you to see how many eggs have
been collected from a farm each day. In Fantasy Chicken League, each farmer
will sign up, give FCL access to his COOP account, and then FCL will count
exactly how many eggs that farm is collecting. And finally, Brent will be
king of the hipster chicken farmers!

In this tutorial, we'll play the part of Brent, using OAuth first to gain
access to his account for a simple script that uses the COOP API to collect
his eggs. From there, we'll learn all about the most well-known OAuth flow
and use it to allow farmers to authorize our Fantasy Chicken League website
to count their eggs.

We'll also learn about the implicit flow that's commonly used by JavaScript
or mobile apps and of course more advanced things like handling refresh tokens.
With all this, we'll show some practical examples of leveraging our expertise
to connect with Facebook on the server-side and via its JavaScript SDK.

Ok, let's go farming!
