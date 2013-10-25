Getting Started with Susan's Website
====================================

This code represents the OAuth2.0 server used in the screencast.  Here is how to run it:

1) Update your vendors via Composer

    php composer.phar install

2) Copy your parameters.yml.dist file to parameters.yml and customize it

    cp app/config/parameters.yml.dist app/config/parameters.yml

3) Fix your permissions

    chmod -R 777 data

4) Setup a virtualhost that points to the web/ directory and a hosts entry
   for your fake domain

5) edit the `data/parameters.json` file found in the `client-finish` library
   to point Hal's website at your new OAuth2.0 server's virtualhost.


