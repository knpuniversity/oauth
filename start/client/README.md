Getting Started with Hal's Website
==================================

This code represents the final product of the screencast. To get things working,
try the following steps (or try to remember what we learned in the video and figure
it out on your own!):

1) Update your vendors via Composer

    php composer.phar install

2) Start a web server. The easiest way to do this is to use the built-in
   PHP web-server (assuming you have PHP 5.4 or higher). From the command line,
   move into this directory and then run:

```
    cd web
    php -S localhost:8000
```

This process will just sit there forever. Press `ctrl+c` later when you're
done with it. You can access the site by going to `http://localhost:8000`.

3) Pop it open in your browser! `http://localhost:8000`

### Requirements

To get this working, you'll need to have the following non-standard PHP extensions
installed:

* php-sqlite

