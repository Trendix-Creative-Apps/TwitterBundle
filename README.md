# Trendix Twitter Bundle

Welcome to our brand new Twitter Bundle. With this bundle, you can easily authenticate a user at twitter so your Symfony 2.8 or 3.*
application can read and tweet on user's behalf with their authorization.

This project is still at alpha, but we are going to improve its functionality and documentation soon enough.

## How can I install this bundle?

Sorry, but at this moment we don't provide a standard way of using it. We plan to register this at composer, though.

## How do I use this bundle?

Once you've installed it, you can take different focuses. But first of all, you should follow these steps:

1. [Create and configure a Twitter app](https://apps.twitter.com/app/new), if you don't have one already.
2. In the app Setting, set the Callback URL to the URL use to login and redirect the user to the app (by default the 
route is called `trendix_twitter_login`, look at TwitterController:twitterLogin).
3. Add to the Kernel our bundle:
````
$bundles = array(
    new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
    ...
    new Trendix\TwitterBundle\TrendixTwitterBundle(),
);
   
````
4. Add also the routing configuration to your routing.yml: 
````
trendix_twitter:
    resource: "@TrendixTwitterBundle/Controller/"
    type:     annotation
    prefix:   /
````
5. Add these four parameters to your parameters.yml with your twitter app data:, at "Keys and Access Tokens" tab:
````
parameters:
    ...
    secret: ThisTokenIsNotSoSecretChangeIt
    twitter_consumer: '' # Consumer Key (API Key)
    twitter_consumer_secret: '' # Consumer Secret (API Secret)
    twitter_access_token: '' # Access Token
    twitter_access_token_secret: '' # Access Token Secret
````

### The simple one

Just follow our DefaultController:test example. Just include our twitter_widget and twitter_includes in your template 
and it should be ready to work.

You can always use your own css to overwrite its style

### Custom your widget

Using our controllers as an example, you can easily change the twig template, css, js or even do completely different 
controllers. The TwitterAPI class is the center of the twitter behaviour, so you can use only that if you want :D

## EU Warning

Our bundle uses the jquery.cookie plugin to store the user token data so the browser can keep their session when 
refreshing or changing pages. Disabling this feature is not recommended, but if you work at the EU, yo have to state
your Cookies Policy and request the user for their consent before using these cookies. 

We recommend using http://cookiecuttr.com/ in order to avoid any legal issues.