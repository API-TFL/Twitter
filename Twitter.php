<?php

require_once 'TwitterAPI.php';

define('TWITTER_USER',    100);
define('TWITTER_HASHTAG', 1000);

final class Twitter
{
    public static function getData($handle, $type)
    {
        $settings = array
        (
            'oauth_access_token'        => TWITTER_OAUTH_ACCESS_TOKEN,
            'oauth_access_token_secret' => TWITTER_OAUTH_ACCESS_TOKEN_SECRET,
            'consumer_key'              => TWITTER_CONSUMER_KEY,
            'consumer_secret'           => TWITTER_CONSUMER_SECRET
        );

        if ($type == 100)
        {
            $getfield = sprintf('?screen_name=%s', trim($handle));
            $url      = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        }

        if ($type == 1000)
        {
            $getfield = sprintf('?q=#%s', trim($handle));
            $url      = 'https://api.twitter.com/1.1/search/tweets.json';
        }

        $twitter  = new TwitterAPI($settings);
        $response = $twitter->setGetfield($getfield)
            ->buildOauth($url, 'GET')
            ->performRequest();

        return (array) json_decode($response);
    }
}