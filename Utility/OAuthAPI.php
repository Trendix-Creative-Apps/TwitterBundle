<?php


namespace Trendix\TwitterBundle\Utility;

use Symfony\Component\HttpFoundation\Session\Session;

class OAuthAPI {
    private $base_url;
    private $ch;
    private $data;
    private $status;
    private $raw_data;
    private $url;
    private $post;
    private $get;
    private $headers = array();
    private $oauth_data;
    private $oauth_secrets;
    private $method;

    public function __construct($baseUrl)
    {
        $dev = '';

        $this->base_url =  $baseUrl;

        //$sessionCookie = 'PHPSESSID=' .session_id() . '; path=/';
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($this->ch, CURLOPT_COOKIE, $sessionCookie);
        curl_setopt($this->ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);

        $this->status = 'NOT SENDED';

        $this->data = null;
        $this->raw_data = null;
        $this->url = null;
        $this->post = array();
        $this->get = array();
        $this->method = 'GET';
    }

    /** Executes the request using curl
     * @return $this
     */
    public function execute()
    {
        $this->serializeGetData();
        if($this->method == 'POST') {
            $this->serializePostData();
        }
        if($this->oauth_data) {
            $this->headers[] = 'OAuth ' . $this->generateOAuthHeaders();
        }
        if(count($this->oauth_data) > 0) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        if($this->method == 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, true);
        }
        $this->raw_data = curl_exec($this->ch);
        $toArray = json_decode($this->raw_data, true);
        if (!is_array($toArray)) {
            if(count($this->oauth_data)) {
                $rawResponse = $this->raw_data;
                $rawResponse = explode("&", $rawResponse);
                $this->data = [];
                foreach ($rawResponse as $item) {
                    $processedItem = explode('=', $item);
                    if(isset($processedItem[1])) {
                        $this->data[$processedItem[0]] = $processedItem[1];
                    }
                }
            } else {
                $this->data = null;
            }
        } else {
            $this->processResponse($toArray);
        }
        return $this;
    }

    private function processResponse($arrayData)
    {
        $this->data = $arrayData;
    }

    /** Adds a path to the base URL used ar the constructor
     * @param string $url
     * @return $this
     */
    public function setURL($url = '')
    {
        $this->url = $this->base_url.$url;
        return $this;
    }

    /** Sets the request method
     * @param $method 'GET|POST'
     * @return $this
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headers
     * @return OAuthAPI
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setOAuthData($oauth_data)
    {
        $this->oauth_data = $oauth_data;
        return $this;
    }

    public function setOAuthSecrets($secrets)
    {
        $this->oauth_secrets = $secrets;
        return $this;
    }

    private function serializeGetData()
    {
        $query_string = '';
        if (!empty($this->get)) {
            $query_string .= '?';
            $query_string .= http_build_query($this->get);
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->url.$query_string);
    }

    private function serializePostData()
    {
        $post_data = '';

        $total = count($this->post);
        $post_data = http_build_query($this->post);

        curl_setopt($this->ch, CURLOPT_POST, $total);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS, $post_data);
    }

    public function addGetData($key, $data)
    {
        $this->get[$key] = $data;
        return $this;
    }

    public function addPostData($key, $data)
    {
        $this->post[$key] = $data;
        return $this;
    }

    private function closeApi()
    {
        curl_close($this->ch);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRawData()
    {
        //Only for debugging
        return $this->raw_data;
    }

    /**
     * Adds authorization headers to the request
     */
    private function generateOAuthHeaders()
    {
        $this->generateOAuthSignature();
        $formattedData = [];
        foreach ($this->oauth_data as $key => $value) {
            $formattedData[] = $key . '="' . rawurlencode($value) . '"';
        }
        $this->headers[] .= 'Authorization: OAuth ' . implode(', ', $formattedData);
    }

    /**
     * Generates the OAuth Signature given all the endpoint request data
     * @return string
     */
    private function generateOAuthSignature()
    {
        $signableOptions = $this->oauth_data;
        $signableOptions = array_merge($signableOptions, $this->post);
        $signableOptions = array_merge($signableOptions, $this->get);
        // Percent encode the entire array (keys and values)
        $newArray = [];
        foreach ($signableOptions as $k => $v) {
            $newArray[rawurlencode($k)] = rawurlencode($v);
        }
        // Then, sort alphabetically by key
        ksort($newArray);
        // Now, put it all in a string
        $string = '';
        foreach ($newArray as $k => $v) {
            $string .= $k . '=' . $v . '&';
        }
        // Take the URL, the encodedParameters and the method, and get the Signature Base string:
        $encodedParameters = trim($string, '&');
        $method = strtoupper($this->method);
        $url = rawurlencode($this->url);
        $signatureBase = $method . '&' . $url . '&' . rawurlencode($encodedParameters);

        // Now, use the secrets to get the signing key:
        $signingKey = rawurlencode($this->oauth_secrets['oauth_consumer_secret']) . '&';
        if (isset($this->oauth_secrets['oauth_token_secret'])) {
            $signingKey .= rawurlencode($this->oauth_secrets['oauth_token_secret']);
        }

        // If this line fails, try passing true as the additional fourth parameter to hash_hmac
        $signature = base64_encode(hash_hmac('sha1', $signatureBase, $signingKey, true));
        $this->oauth_data['oauth_signature'] = $signature;
        return $signature;
    }
}