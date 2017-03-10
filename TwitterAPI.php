<?php

/**
 * Twitter: Simple PHP wrapper for the v1.1 API
 *
 * Required: PHP version 5.3.10
 *
 * @package  Twitter
 * @author   Travis van der Font <travis.font@gmail.com>
 * @license  MIT License
 * @version  1.0.0
 * @link     https://github.com/API-TFL/Twitter
 */
class TwitterAPI
{
    /**
     * @var string
     */
    private $_oauth_access_token;

    /**
     * @var string
     */
    private $_oauth_access_token_secret;

    /**
     * @var string
     */
    private $_consumer_key;

    /**
     * @var string
     */
    private $_consumer_secret;

    /**
     * @var array
     */
    private $_postfields;

    /**
     * @var string
     */
    private $_getfield;

    /**
     * @var mixed
     */
    protected $_oauth;

    /**
     * The HTTP status code from the previous request
     *
     * @var int
     */
    protected $_http_status_code;

    /**
     * If the request protocol is SSL or not
     *
     * @var bool
     */
    protected $_ssl;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $request_method;

    /**
     * Create the API access object. Requires an array of settings::
     * oauth access token, oauth access token secret, consumer key, consumer secret
     * These are all available by creating your own application on dev.twitter.com
     * Requires the cURL library
     *
     * @throws \RuntimeException When cURL isn't loaded
     * @throws \InvalidArgumentException When incomplete settings parameters are provided
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (!function_exists('curl_init'))
        {
            throw new RuntimeException('TwitterAPI requires cURL extension to be loaded, see: http://curl.haxx.se/docs/install.html');
        }

        if (   !isset($settings['oauth_access_token'])
            || !isset($settings['oauth_access_token_secret'])
            || !isset($settings['consumer_key'])
            || !isset($settings['consumer_secret']))
        {
            throw new InvalidArgumentException('Incomplete settings passed to TwitterAPI');
        }

        $this->_oauth_access_token        = $settings['oauth_access_token'];
        $this->_oauth_access_token_secret = $settings['oauth_access_token_secret'];
        $this->_consumer_key              = $settings['consumer_key'];
        $this->_consumer_secret           = $settings['consumer_secret'];
    }

    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     *
     * @param array $array Array of parameters to send to API
     *
     * @throws \Exception When you are trying to set both get and post fields
     *
     * @return TwitterAPI Instance of self for method chaining
     */
    public function setPostfields(array $array)
    {
        if (!is_null($this->getGetfield()))
        {
            throw new Exception('You can only choose get OR post fields.');
        }

        if (isset($array['status']) && substr($array['status'], 0, 1) === '@')
        {
            $array['status'] = sprintf("\0%s", $array['status']);
        }

        foreach ($array as $key => &$value)
        {
            if (is_bool($value))
            {
                $value = ($value === TRUE) ? 'true' : 'false';
            }
        }

        $this->_postfields = $array;

        // rebuild oAuth
        if (isset($this->oauth['oauth_signature']))
        {
            $this->buildOauth($this->url, $this->request_method);
        }

        return $this;
    }

    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     *
     * @param string $string Get key and value pairs as string
     *
     * @throws \Exception
     *
     * @return \TwitterAPI Instance of self for method chaining
     */
    public function setGetfield($string)
    {
        if (!is_null($this->getPostfields()))
        {
            throw new Exception('You can only choose get OR post fields.');
        }

        $getfields = preg_replace('/^\?/', '', explode('&', $string));
        $params    = array();

        foreach ($getfields as $field)
        {
            if ($field !== '')
            {
                list($key, $value) = explode('=', $field);
                $params[$key]      = $value;
            }
        }

        $this->_getfield = '?'.http_build_query($params);

        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function getGetfield()
    {
        return $this->_getfield;
    }

    /**
     * Get postfields array (simple getter)
     *
     * @return array $this->_postfields
     */
    public function getPostfields()
    {
        return $this->_postfields;
    }

    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     * @param string $url           The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
     * @param string $request_method Either POST or GET
     *
     * @throws \Exception
     *
     * @return \TwitterAPI Instance of self for method chaining
     */
    public function buildOauth($url, $request_method)
    {
        if (strtoupper(substr_replace($url, '', strpos($url, '://'))) === 'HTTPS')
        {
            $this->_ssl = TRUE;
        }

        if (!in_array(strtolower($request_method), array('post', 'get')))
        {
            throw new Exception('Request method must be either POST or GET');
        }

        $_consumer_key              = $this->_consumer_key;
        $_consumer_secret           = $this->_consumer_secret;
        $_oauth_access_token        = $this->_oauth_access_token;
        $_oauth_access_token_secret = $this->_oauth_access_token_secret;

        $oauth = array
        (
            'oauth_consumer_key'     => $_consumer_key,
            'oauth_nonce'            => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token'            => $_oauth_access_token,
            'oauth_timestamp'        => time(),
            'oauth_version'          => '1.0'
        );

        $_getfield = $this->getGetfield();

        if (!is_null($_getfield))
        {
            $getfields = str_replace('?', '', explode('&', $_getfield));

            foreach ($getfields as $g)
            {
                $split = explode('=', $g);

                /** In case a null is passed through **/
                if (isset($split[1]))
                {
                    $oauth[$split[0]] = urldecode($split[1]);
                }
            }
        }

        $_postfields = $this->getPostfields();

        if (!is_null($_postfields))
        {
            foreach ($_postfields as $key => $value)
            {
                $oauth[$key] = $value;
            }
        }

        $base_info                = $this->buildBaseString($url, $request_method, $oauth);
        $composite_key            = rawurlencode($_consumer_secret).'&'.rawurlencode($_oauth_access_token_secret);
        $oauth_signature          = base64_encode(hash_hmac('sha1', $base_info, $composite_key, TRUE));
        $oauth['oauth_signature'] = $oauth_signature;

        $this->url             = $url;
        $this->request_method  = $request_method;
        $this->_oauth          = $oauth;

        return $this;
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return      If true, returns data. This is left in for backward compatibility reasons
     * @param array   $curl_options Additional Curl options for this request
     *
     * @throws \Exception
     *
     * @return string json If $return param is true, returns json data.
     */
    public function performRequest($return = TRUE, $curl_options = array())
    {
        if (!is_bool($return))
        {
            throw new Exception('performRequest parameter must be true or false');
        }

        $header      = array($this->buildAuthorizationHeader($this->_oauth), 'Expect:');
        $_getfield   = $this->getGetfield();
        $_postfields = $this->getPostfields();

        $options = array
        (
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_HEADER         => FALSE,
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 10,
        ) + $curl_options;

        if (!is_null($_postfields))
        {
            $options[CURLOPT_POSTFIELDS] = http_build_query($_postfields);
        }
        else
        {
            if ($_getfield !== '')
            {
                $options[CURLOPT_URL] .= $_getfield;
            }
        }

        if (isset($this->_ssl) && $this->_ssl === TRUE)
        {
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
            $options[CURLOPT_SSL_VERIFYPEER] = 0;
        }

        $feed = curl_init();

        curl_setopt_array($feed, $options);

        $json = curl_exec($feed);

        $this->_http_status_code = curl_getinfo($feed, CURLINFO_HTTP_CODE);

        if (($error = curl_error($feed)) !== '')
        {
            curl_close($feed);

            throw new \Exception($error);
        }

        curl_close($feed);

        return $json;
    }

    /**
     * Private method to generate the base string used by cURL
     *
     * @param string $base_uri
     * @param string $method
     * @param array  $params
     *
     * @return string Built base string
     */
    private function buildBaseString($base_uri, $method, $params)
    {
        $return = array();

        ksort($params);

        foreach ($params as $key => $value)
        {
            $return[] = rawurlencode($key).'='.rawurlencode($value);
        }

        return $method.'&'.rawurlencode($base_uri).'&'.rawurlencode(implode('&', $return));
    }

    /**
     * Private method to generate authorization header used by cURL
     *
     * @param array $_oauth Array of oauth data generated by buildOauth()
     *
     * @return string $return Header used by cURL for request
     */
    private function buildAuthorizationHeader(array $oauth)
    {
        $return     = 'Authorization: OAuth ';
        $values     = array();
        $oauth_keys = array
        (
            'oauth_consumer_key',
            'oauth_nonce',
            'oauth_signature',
            'oauth_signature_method',
            'oauth_timestamp',
            'oauth_token',
            'oauth_version'
        );

        foreach ($oauth as $key => $value)
        {
            if (in_array($key, $oauth_keys))
            {
                //$values[] = "$key=\"".rawurlencode($value)."\"";
                $values[] = vsprintf('%s="%s"', array($key, rawurlencode($value)));
            }
        }

        $return .= implode(', ', $values);

        return $return;
    }

    /**
     * Helper method to perform our request
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param array  $curl_options
     *
     * @throws \Exception
     *
     * @return string The json response from the server
     */
    public function request($url, $method = 'get', $data = NULL, $curl_options = array())
    {
        if (strtolower($method) === 'get')
        {
            $this->setGetfield($data);
        }
        else
        {
            $this->setPostfields($data);
        }

        return $this->buildOauth($url, $method)->performRequest(TRUE, $curl_options);
    }

    /**
     * Get the HTTP status code for the previous request
     *
     * @return integer
     */
    public function getHttpStatusCode()
    {
        return $this->_http_status_code;
    }
}
