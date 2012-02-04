<?php

// Create an app here: https://www.yourtrove.com/developers/applications

// Then copy over these three variables

$app_key			= 'app_key';
$app_secret			= 'app_secret';
$app_callback_url	= 'app_callback_url';

// Now run this page to authorize yourself and generate an access token which you can copy in here

$access_token = null;

// Include and initialize trove

include 'trove.php';
$trove = new Trove($app_key, $app_secret, $app_callback_url, $access_token);

// Have we got an access token copy and pasted in above yet? If not then authorize the user.

if (!$access_token) {
	
	if (!$_GET['code']) {
		
		// No code found so show auth link
		
		$authUrl = $trove->buildAuthUrl();
		echo '<p><a href="'.$authUrl.'">Click here</a> to authorize Trove</p>';
		
	} elseif ($_GET['code']) {
		
		// Code spotted in url so generate an access token
		
		$access_token = $trove->getAccessToken($_GET['code']);
		echo '<p>Here\'s your access token: '.$access_token.'<br />Copy and paste this into your code so you can play with the API calls.</p>';
		// Eventually of course you'll want to save this in your database
		
	}
	
}

if ($access_token) {
	
	// We can haz access token! Let's play!
	
	$user = $trove->getUserInformation();
	
	//echo '<pre>';
	//print_r($user);
	//echo '<pre>';
	
	echo '<p>Now '.$user['first_name'].' let\'s try fetching some photos:</p>';
	
	$photos = $trove->getPhotos();
	
	//echo '<pre>';
	//print_r($photos);
	//echo '<pre>';
	
	foreach ($photos['results'] as $photo) {
		echo '<a href="'.$photo['original_web_url'].'"><img src="'.$photo['urls']['thumbnail'].'" alt="'.$photo['title'].'" title="'.$photo['title'].'" /></a> ';
	}
	
	// Now try these
	
	//echo '<pre>';
	//print_r($trove->getCheckins());
	//echo '<pre>';
	
	//echo '<pre>';
	//print_r($trove->getStatus());
	//echo '<pre>';
	
}

?>