<?php

namespace Trendix\TwitterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Trendix\TwitterBundle\Utility\TwitterAPI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * @package Trendix\TwitterBundle\Controller
 * @Route("/twitter")
 */
class TwitterController extends TwitterAPI
{
    /**
     * @Route("/test", name="trendix_twitter_test")
     * @Method({"GET", "POST"})
     */
    public function testAction(Request $request)
    {
        $this->addData($request->request->all());
        return $this->render('TrendixTwitterBundle::test.html.twig', $this->getData());
    }

    /**
     * @Route("/login", name="trendix_twitter_login")
     */
    public function twitterLoginAction(Request $request)
    {
        $token = $request->query->has('oauth_token') ? $request->query->get('oauth_token') : null;
        $verifier = $request->query->has('oauth_verifier') ? $request->query->get('oauth_verifier') : null;
        $this->addData($this->twitterLogin($token, $verifier));

        return $this->render("TrendixTwitterBundle::login.html.twig", $this->getData());

    }

    /**
     * @Route("/request-token", name="trendix_twitter_request_token")
     */
    public function oauthRequestTokenAction()
    {
        return new JsonResponse($this->oauthRequestToken());
    }

    /**
     * @Route("/send-tweet", name="trendix_twitter_send_tweet")
     * @Method("POST")
     */
    public function oauthTweetAction(Request $request)
    {
        $token = $request->request->get('oauth_token');
        $tokenSecret = $request->request->get('oauth_token_secret');
        $status = $request->request->get('status');
        $reply = $request->request->get('reply');
        $response = $this->sendTweet($token, $tokenSecret, $status, $reply);
        return new JsonResponse($response);
    }

    /**
     * @Route("/search", name="trendix_twitter_search")
     * @Method("POST")
     */
    public function twitterSearchAction(Request $request)
    {
        $token = $request->request->get('oauth_token');
        $tokenSecret = $request->request->get('oauth_token_secret');
        $search = array(
            'q' => '%40trendix_es',
            'result_type' => 'recent'
        );
        $results = $this->searchTweets($token, $tokenSecret, $search);
        return new JsonResponse($results);
    }

    /**
     * @Route("/get-tweet", name="trendix_twitter_get_tweet")
     * @Method("POST")
     */
    public function singleTweetAction(Request $request)
    {
        $token = $request->request->get('oauth_token');
        $tokenSecret = $request->request->get('oauth_token_secret');
        $id = $request->request->get('tweet_id');
        $result = $this->getSingleTweet($token, $tokenSecret, $id);
        return new JsonResponse($result);
    }
}
