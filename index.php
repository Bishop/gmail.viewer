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

function get_request($url, $params = array(), $sign_token = null)
{
	$consumer_key = 'anonymous';
	$consumer_secret = 'anonymous';

	$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

	$request = OAuthRequest::from_consumer_and_token($consumer, null, "GET", $url, $params);

	$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1, $consumer, $sign_token);

	return $request;
}

function get_token($url, $params = array(), $sign_token = null)
{
	$request = get_request($url, $params, $sign_token);

	$response = file_get_contents($request->to_url());

	$oauth_tokens = array();
	parse_str($response, $oauth_tokens);

	return new OAuthToken($oauth_tokens['oauth_token'], $oauth_tokens['oauth_token_secret']);
}

session_start();

if (isset($_GET['oauth_token'])) {
	$request_token = $_SESSION['request_token'];

	$access_token = get_token(
		$google_oauth['accessTokenUrl'],
		array('oauth_token' => $request_token->key),
		$request_token
	);
	$_SESSION['token'] = $access_token;
	unset($_SESSION['request_token']);
	header("Location: /");
	exit;
}

$token = @$_SESSION['token'];

if (!$token) {
	$token = get_token($google_oauth['requestTokenUrl'], array('scope' => $google_services['mail_feed']));
	$_SESSION['request_token'] = $token;

	$callback_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	$url = $google_oauth['userAuthorizationUrl'] .
			"?oauth_token={$token->key}&oauth_callback=" . urlencode($callback_url);

	header("Location: $url");
	exit();
}

$request = get_request($google_services['mail_feed'], array('oauth_token' => $token->key), $token);

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