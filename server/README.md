Getting Started with the OAuth Server (Your House)
==================================================

This code represents the OAuth2.0 server used in the screencast.  Here is how to run it:

1) Update your vendors via Composer

    php composer.phar install

2) Fix your permissions

```
  $ chmod -R 777 data
```

3) On the command line, start up a new PHP server on localhost:9000

```
  $ cd /path/to/knpuniversity/oauth/server-finish/web
  $ php -S localhost:9000
```

4) Pop open `http://localhost:9000` in your browser!


