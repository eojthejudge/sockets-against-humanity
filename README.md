# sockets-against-humanity
Websocket card game trying out Swoole PHP and Vue.js

## Development

### Requirements

* PHP 7.4
* Npm
* Swoole extension for PHP
* Composer
* libpq-dev
* Swoole postgresql extension at https://github.com/swoole/ext-postgresql

### Server

Copy cards data as json from https://www.crhallberg.com/cah/ in server/data/cah-cards.json
Download the compact format

```
cd server
composer install
php server.php
```

### Client

```
cd sah-client
npm run serve
```
