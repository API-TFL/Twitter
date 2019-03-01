# Twitter
Simple PHP Wrapper for Twitter API (recently updated)

The aim of this class is simple. You need to:

- Include the class in your PHP code
- [Create a twitter app on the twitter developer site](https://dev.twitter.com/apps/)
- Enable read/write access for your twitter app
- Grab your access tokens from the twitter developer site
- [Choose a twitter API URL to make the request to](https://dev.twitter.com/docs/api/1.1/)

It really can't get much simpler than that.

Installation
------------

**Normally:** If you *don't* use composer, don't worry - just include Twitter.php in your application. 

**Via Composer:** 

    {
        "require": {
            "API-TFL/Twitter": "dev-master"
        }
    }

Of course, you'll then need to run `php composer.phar update`.

How To Use
----------

See "example.php" file for practical example usage.

#### Set access tokens ####

```php
define('TWITTER_OAUTH_ACCESS_TOKEN',        '132-yabcDHkjKyabcDHkjKyabcDHkjKyabcDHkjK');
define('TWITTER_OAUTH_ACCESS_TOKEN_SECRET', 'yabcDHkjKyasssKyabcDHkjKyadddbcDHkjK');
define('TWITTER_CONSUMER_KEY',              '1sd32a1sd21a2d31s32a1');
define('TWITTER_CONSUMER_SECRET',           'tDASDSAOpopopokdDADsasadsa');

```

#### And then Include the class file ####

```php
require_once 'Twitter.php';
```
