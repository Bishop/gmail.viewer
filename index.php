<?php

require_once 'OAuth.php';

$google_services = array(
	'mail_feed' => 'https://mail.google.com/mail/feed/atom',
);

$google_oauth = array(
	'requestTokenUrl' => 'https://www.google.com/accounts/OAuthGetRequestToken',
	'userAuthorizationUrl' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
	'accessTokenUrl' => 'https://www.google.com/accounts/OAuthGetAccessToken',
);

function get_request($url, $params = array(), $token = null)
{
	$consumer_key = 'anonymous';
	$consumer_secret = 'anonymous';

	$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

	$request = OAuthRequest::from_consumer_and_token($consumer, null, "GET", $url, $params);

	$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1, $consumer, $token);

	return $request;
}

function get_tokens($url, $params = array(), $token = null)
{
	$request = get_request($url, $params, $token);

	$response = file_get_contents($request->to_url());

	$oauth_tokens = array();
	parse_str($response, $oauth_tokens);

	return $oauth_tokens;
}

session_start();

if (isset($_GET['oauth_token'])) {
	$oauth_tokens = $_SESSION['tokens'];

	$oauth_tokens = get_tokens(
		$google_oauth['accessTokenUrl'],
		array('oauth_token' => $oauth_tokens['oauth_token']),
		new OAuthToken($oauth_tokens['oauth_token'], $oauth_tokens['oauth_token_secret'])
	);
	$_SESSION['token'] = $oauth_tokens['oauth_token'];
	$_SESSION['tokens'] = $oauth_tokens;
	header("Location: /");
	exit;
}

$token = @$_SESSION['token'];

if (!$token) {
	$oauth_tokens = get_tokens($google_oauth['requestTokenUrl'], array('scope' => $google_services['mail_feed']));
	$_SESSION['tokens'] = $oauth_tokens;

	$callback_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	$url = $google_oauth['userAuthorizationUrl'] . "?oauth_token={$oauth_tokens['oauth_token']}&oauth_callback=" . urlencode($callback_url);

	header("Location: $url");
	exit();
}

$oauth_tokens = $_SESSION['tokens'];

$request = get_request(
	$google_services['mail_feed'],
	array('oauth_token' => $oauth_tokens['oauth_token']),
	new OAuthToken($oauth_tokens['oauth_token'], $oauth_tokens['oauth_token_secret'])
);


$options = array(
	CURLOPT_URL => $google_services['mail_feed'],
	CURLOPT_HTTPHEADER => array($request->to_header()),
	CURLOPT_HEADER => false,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false
);

$ch = curl_init();
curl_setopt_array($ch, $options);
$response = curl_exec($ch);

var_dump($response);