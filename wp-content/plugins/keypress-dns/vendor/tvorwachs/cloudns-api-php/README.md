ClouDNS API Library
===============================

This is a wrapper library for the ClouDNS API to be used by PHP applications. It tries to ease the integration of the API into your applications by handling all interactions with API and providing a simple interface to interact with.



Installation
-------------------------------
Add this requirement to your `composer.json` file and run `composer.phar install`:

    {
        "require": {
            "tvorwachs/cloudns-api-php": "*@dev-master"
        }
    }

or

run `composer require tvorwachs/cloudns-api-php`

Getting Started
-------------------------------
To begin using the Library, the cloudns.php must be included in your application.

```
use tvorwachs\ClouDNS;
```

An instance of the ClouDNS must be created to interact with the library. This Object is the gateway to all interactions with the library. The API password obtained from the [ClouDNS](https://www.cloudns.net/api-settings/) must be passed into the ClouDNS by calling set_options.

```php
$cloudns = new ClouDNS();
$cloudns->setOptions(array('authId' => '999','authPassword' => 'some_password', 'authType' => 'auth-id'));
```

Functions
-------------------------------

View at [tobee94.github.io](https://tobee94.github.io/cloudns-api-php)

Reporting Issues/Contributing
-------------------------------
If you find an issue with the library, please report the issue to us by using the repository's issue tracker and we will try to resolve the issue. If you resolve the issue or make other improvements feel free to create a pull request so we can merge it into a future release.
