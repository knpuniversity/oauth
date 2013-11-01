Getting Started with the House Website
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

   * Be sure to set "AllowOverride All" in your apache configuration.  Otherwise,
     the logic defined in `.htaccess` will not be loaded:

```
#
# Knp OAuth Server
#
<VirtualHost *:80>
    ServerName knp-oauth-server

    DocumentRoot "/Library/WebServer/knpuniversity/oauth/server-finish/web"
    <Directory "/Library/WebServer/knpuniversity/oauth/server-finish/web">
        AllowOverride All
        Allow from All
    </Directory>
</VirtualHost>
```

5) edit the `data/parameters.json` file found in the `client-finish` library
   to point Hal's website at your new OAuth2.0 server's virtualhost.


