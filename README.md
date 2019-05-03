## TOMLConfig

Write your config in `.toml` files (https://github.com/toml-lang/toml) and use them in with https://github.com/hassankhan/config.

Toml Parser used: https://github.com/yosymfony/toml

### Config
Supports overloading and uses the first passed-in file as default values.

### Interpolation
Supports simple variable interpolation within the same config file:

```toml
basepath = "/var/www"
publicpath = "${basepath}/public"
```

```php
<?php
$config = new Alpipego\TomlConfig\TomlConfig(['tomlfile.toml']);

$config->get('public');
// => `/var/www/public`
```

This is also possible with arrays.
Within the same array a simple notation can be used:

```toml
[paths]
base = "/var/www"
public = "${base}/public" # /var/www/public
```

It's preferable to use the full variable path to avoid conflicts:

```toml
[paths]
base = "/var/www"
public = "${paths.base}/public" # /var/www/public
```


Recursion is possible if the previous values have been expanded:
```toml
[paths]
base = "/var/www"
public = "${paths.base}/public" # /var/www/public
assets = "${paths.public}/assets" # /var/www/public/assets
```

## $ENV

The configuration variables are put into environment variables. Array keys (and other complex values) get expanded like:

```toml
[paths]
base = "/var/www"

[paths.app]
dir = "${paths.base}/app"
```

This results in 

```.env
PATHS_BASE=/var/www
PATHS_APP_DIR=/var/www/app
```

```php
<?php
getenv('PATHS_APP_DIR'); // '/var/www/app' 
$_ENV('PATHS_APP_DIR'); // '/var/www/app' 
```
Note: If an env var already exists it does not get overridden.


### Write `.env` file

If you want to use your configuration on your command line, you can write a `.env` file and source it in your shell:

```php
<?php
$config = new \Alpipego\TomlConfig\TomlConfig(['file1', 'file2']);

$config->writeEnv(__DIR__);
``` 

Notes:
* The path to the `.env` has to be writable by the webserver
* This file gets written on every call of the function
