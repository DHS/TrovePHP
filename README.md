Trove PHP library
=================

This is the PHP library for [Trove](http://www.yourtrove.com). It is licensed under the MIT license (see LICENSE). For more information and to and generate your app id and secret, visit the [Developers](http://www.yourtrove.com/developers/) section of our website.

Getting started
---------------

See demo.php. Here's the basic flow.

First initialize Trove:

	$trove = new Trove($app_key, $app_secret, $app_callback_url);
	$authUrl = $trove->buildAuthUrl();

Then redirect users to $authUrl. The bounceback will return a GET var called code that you can use to generate an access token:

	$access_token = $trove->getAccessToken($_GET['code']);

You can then save the access token and re-use it later like this:

	$trove = new Trove($app_key, $app_secret, $app_callback_url, $access_token);

Sample method calls:

	$trove->getUserInformation();
	$trove->getPhotos();
	$trove->getCheckins();
	$trove->getStatus();
	$trove->getPhotos(array('services' => 'facebook', 'page' => '2'));
