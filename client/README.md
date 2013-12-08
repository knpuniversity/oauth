Getting Started with Hal's Website
==================================

This code represents the final product of the screencast. To get things working,
try the following steps (or try to remember what we learned in the video and figure
it out on your own!):

1) Update your vendors via Composer

```cli
    php composer.phar install
```

2) Setup a virtualhost that points to the web/ directory and a hosts entry
   for your fake domain

```config
#
# Knp OAuth Client
#
<VirtualHost *:80>
    ServerName knp-oauth-client

    DocumentRoot "/Library/WebServer/knpuniversity/oauth/client-finish/web"
    <Directory "/Library/WebServer/knpuniversity/oauth/client-finish/web">
        AllowOverride All
        Allow from All
    </Directory>
</VirtualHost>
```

3) (Optional) you can customize parameters.json

```cli
    cp client/data/parameters.json.dist client/data/parameters.json
```

4) Pop it open in your browser!

```cli
    php -S localhost:9000
```
