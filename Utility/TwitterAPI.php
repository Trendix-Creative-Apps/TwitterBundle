<?php
/**
 * Created by PhpStorm.
 * User: jose
 * Date: 16/5/17
 * Time: 9:57
 */

namespace Trendix\TwitterBundle\Utility;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TwitterAPI extends Controller
{
    private $data = array();


    /**
     * Añdds an element to the data array
     * @param $key string|array The key which value we are adding or the array with the key-value pairs
     * @param $value string|null If a key is defined, value for that key
     * @return $this
     */
    protected function addData($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $dato) {
                $this->addData($name, $dato);
            }
        } else {
            $this->data[$key] = $value;
            return $this;
        }

    }

    /**
     * Get all added data
     * @return array
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * 1st step, requests the request token. With the returned token, the browser must navigate to https://api.twitter.com/oauth/authorize?oauth_token=<token>
     * @return array With oauth_token
     */
    protected function oauthRequestToken()
    {
        $curl = new OAuthAPI('https://api.twitter.com/');
        $curl->setURL('oauth/request_token');
        $curl->setMethod('POST');
        $nonce = uniqid();
        $timestamp = date_timestamp_get(new \DateTime());
        $consumerKey = $this->getParameter('twitter_consumer');
        $consumerSecret = $this->getParameter('twitter_consumer_secret');
        $token = $this->getParameter('twitter_access_token');
        $oauthOptions = [
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_consumer_key' => $consumerKey,
            'oauth_version' => '1.0'
        ];
        $oauthSecrets = [
            'oauth_consumer_secret' => $consumerSecret
        ];
        $curl->setOAuthData($oauthOptions);
        $curl->setOAuthSecrets($oauthSecrets);
        $curl->execute();
        return $curl->getData();
    }


    /**
     * 2nd step. After the user authorizes the application, we must retrieve the access token, using the token
     * and verifier received from step 1.
     * @param $token oauth_token
     * @param $verifier oauth_verifier
     * @return array With oauth_token, oauth_token_secret, user_id and screen_name
     */
    protected function twitterLogin($token, $verifier)
    {
        $curl = new OAuthAPI('https://api.twitter.com/');
        $curl->setURL('oauth/access_token');
        $curl->setMethod('POST');
        $nonce = uniqid();
        $callback = urlencode('/twitter-login');
        $timestamp = date_timestamp_get(new \DateTime());
        $consumerKey = $this->getParameter('twitter_consumer');
        $oauthOptions = [
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_consumer_key' => $consumerKey,
            'oauth_version' => '1.0',
            'oauth_token' => $token,
            'oauth_verifier' => $verifier
        ];
        $curl->setOAuthData($oauthOptions);
        $curl->execute();
        $this->addData($curl->getData());

        return $curl->getData();
    }

    /** Sends a tweet in behalf of the authenticated user
     * @param $token oauth_token
     * @param $tokenSecret oauth_token_secret
     * @param $status Tweet text
     * @param string|integer|null $replyToId ID of the tweet this twwet is replying to, if needed
     * @param string|null $prependText String to concatenate before the rest of the text
     * @param string|null $appendText String to concatenate before the rest of the text
     * @return array Tweet data / error info
     */
    protected function sendTweet($token, $tokenSecret, $status, $replyToId = null, $prependText = null, $appendText = null)
    {
        $curl = new OAuthAPI('https://api.twitter.com/');
        $curl->setURL('1.1/statuses/update.json');
        $curl->setMethod('POST');
        $this->setAuthData($token, $tokenSecret, $curl);
        if($appendText) {
            $status = $status . $appendText;
        }
        if($prependText) {
            $status = $prependText . $status;
        }
        $curl->addGetData('status', $status);

        if($replyToId) {
            $curl->addPostData('in_reply_to_status_id', $replyToId);
        }
        $curl->execute();
        return $curl->getData();
    }

    /**
     * Use this as guide for making search queries https://dev.twitter.com/rest/public/search
     * @param $token oauth_token
     * @param $tokenSecret oauth_token_secrent
     * @param $searchParameters array search parameters. "q" is the basic parameter for searches. use %40 for @ and %23 for #.
     * @return array Array of tweets, presented as described here: https://dev.twitter.com/rest/reference/get/search/tweets
     */
    protected function searchTweets($token, $tokenSecret, $searchParameters)
    {
        $curl = new OAuthAPI('https://api.twitter.com/');
        $curl->setURL('1.1/search/tweets.json');
        $curl->setMethod('GET');
        $this->setAuthData($token, $tokenSecret, $curl);
        foreach ($searchParameters as $key => $value) {
            $curl->addGetData($key, $value);
        }
        $curl->execute();
        return $curl->getData();
    }

    /** Retrieves a specific tweet given its ID
     * @param $token string oauth_token
     * @param $tokenSecret string oauth_token_secret
     * @param $tweetId integer|string Tweet ID
     * @return array Tweet data as shown in https://dev.twitter.com/rest/reference/get/statuses/show/id
     */
    protected function getSingleTweet($token, $tokenSecret, $tweetId)
    {
        $curl = new OAuthAPI('https://api.twitter.com/');
        $curl->setURL('1.1/statuses/show.json');
        $curl->setMethod('GET');
        $this->setAuthData($token, $tokenSecret, $curl);
        $curl->addGetData('id', $tweetId);
        $curl->execute();
        return $curl->getData();
    }

    /**
     * @param $token
     * @param $tokenSecret
     * @param $curl
     */
    private function setAuthData($token, $tokenSecret, $curl)
    {
        $nonce = uniqid();
        $timestamp = date_timestamp_get(new \DateTime());
        $consumerKey = $this->getParameter('twitter_consumer');
        $consumerSecret = $this->getParameter('twitter_consumer_secret');
        $oauthOptions = [
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_consumer_key' => $consumerKey,
            'oauth_version' => '1.0',
            'oauth_token' => $token
        ];
        $oauthSecrets = [
            'oauth_consumer_secret' => $consumerSecret,
            'oauth_token_secret' => $tokenSecret
        ];
        $curl->setOAuthData($oauthOptions);
        $curl->setOAuthSecrets($oauthSecrets);
    }
}