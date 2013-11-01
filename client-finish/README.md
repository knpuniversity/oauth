Getting Started with Hal's Website
==================================

This code represents the final product of the screencast. To get things working,
try the following steps (or try to remember what we learned in the video and figure
it out on your own!):

1) Update your vendors via Composer

    php composer.phar install

2) Copy your parameters.yml.dist file to parameters.yml and customize it

    cp app/config/parameters.yml.dist app/config/parameters.yml

3) Fix your permissions

    chmod -R 777 data

4) Setup a virtualhost that points to the web/ directory and a hosts entry
   for your fake domain

```
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

5) Pop it open in your browser!


