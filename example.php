<?php

define('TWITTER_OAUTH_ACCESS_TOKEN',        '132-yabcDHkjKyabcDHkjKyabcDHkjKyabcDHkjK');
define('TWITTER_OAUTH_ACCESS_TOKEN_SECRET', 'yabcDHkjKyasssKyabcDHkjKyadddbcDHkjK');
define('TWITTER_CONSUMER_KEY',              '1sd32a1sd21a2d31s32a1');
define('TWITTER_CONSUMER_SECRET',           'tDASDSAOpopopokdDADsasadsa');

require_once 'Twitter.php';

/* retrieve data (user) */
$user_data = Twitter::getData('user_name_here', TWITTER_USER);
print_r($user_data);

//---
echo '----------------------------------------';
//---

/* retrieve data (hashtag) */
$hastag_data = Twitter::getData('hashtag_here', TWITTER_HASHTAG);
print_r($hastag_data);