<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011 Aaron hedges
 * Copyright (c) 2011 Nick Vlku
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software. 
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**
 * 
 * @author Aaron Hedges <aaron@dashron.com>
 * @author Nick Vlku <n@yourtrove.com>
 * @version 
 */
class Trove {
	
	public $clientId;
	public $clientSecret;
	public $redirectUri;
	public $scope;
	public $accessToken;
	
	private static $rootUrl			= 'https://api.yourtrove.com/v2/';
	private static $authenticateUrl	= 'https://www.yourtrove.com/oauth2/authenticate/';
	private static $authorizeUrl	= 'https://www.yourtrove.com/oauth2/access_token';

	const VERSION = '2.0';
	
	/**
	 * Consumer Key and Consumer Secret are required here. These are your OAuth credentials
	 * 
	 * @param string $clientId Required, contains the OAuth consumer key
	 * @param string $clientSecret Required, contains the OAuth consumer secret
	 * @param string $redirectUri Required, contains the redirectURI for your app specified in YourTrove
	 * @param string $accessToken Optional, if you already have this authenticated 
	 * @param string $scope Optional, a list of content types you want.  Default is array('photos')
	 */
	public function __construct($clientId, $clientSecret, $redirectUri, $accessToken = null, $scope = array('photos')) {
		
		$this->clientId		= $clientId;
		$this->clientSecret	= $clientSecret;
		$this->redirectUri	= $redirectUri;
		$this->scope		= $scope;
		$this->accessToken	= $accessToken;
		
	}
	
	/**
	 * First step of OAuth2. Gets an authenticate request that will bounce back a code that you will use
	 * to create an Access Token.
	 * 
	 * @return string Url that the user should be redirected to.
	 */
	public function buildAuthURL() {
		
		$params['client_id']		= $this->clientId;
		$params['response_type']	= 'code';
		$params['redirect_uri']		= $this->redirectUri;
		
		return HttpUtil::createGetUrl(self::$authenticateUrl, $params);   
		
	}
	
	/**
	 * Second step of OAuth2. Generate the access token.
	 * 
	 * @param string $codeToken Required, the code from the first stage of OAuth
	 */
	public function getAccessToken($codeToken) {
		
		$params['client_id']		= $this->clientId;
		$params['grant_type']		= 'authorization_code';
		$params['redirect_uri']		= $this->redirectUri;
		$params['client_secret']	= $this->clientSecret;
		$params['code']				= $codeToken;
		
		$response = HttpUtil::httpRequest('GET', self::$authorizeUrl, $params);
		$data = json_decode($response);
		$this->accessToken = $data->{'access_token'};
		
	}
	
	/**
	 * Performs an HTTP POST request along with the necessary OAuth information, and signature
	 * 
	 * @param string $url Required, the url to send the request to
	 * @param arrray $params Optional, the params to send to the url
	 * @return $string The body of the HTTP response
	 */
	function post($url, $params = array()) {
		
		$url = self::$rootUrl . $url;
		$params = $this->buildRequest('POST', $url, $params);
		
		return HttpUtil::httpRequest('POST', $url, $params);
		
	}
	
	/**
	 * Performs an HTTP GET request along with the necessary OAuth information, and signature
	 * 
	 * @param string $url Required, the url to send the request to
	 * @param arrray $params Optional, the params to send to the url
	 * @return $string The body of the HTTP response
	 */
	function get($url, $params = array()) {
		
		$url = self::$rootUrl . $url;
		$params = $this->buildRequest('GET', $url, $params);
		
		return HttpUtil::httpRequest('GET', $url, $params);
		
	}
	
	/**
	 * OAuth uses a different url encode scheme than php, so this function ensures compliance
	 * 
	 * @param string $data Required, the data to clean
	 * @return string The cleaned data
	 */
	protected static function clean($data) {
		
		$data = utf8_encode($data);
		$data = rawurlencode($data);
		//cheating and un-doing the non-rfc compliant encoding
		//TODO: Do this the right way?
		return str_replace('+',' ', str_replace('%7E', '~', $data));
		
	}
	
	/**
	 * Get information about a user
	 * 
	 * @return array An array of user data
	 */
	public function getUserInformation() {
		
		if ($this->accessToken == null) {
			throw new AccessTokenRequiredException();
		}
		
		$params['access_token'] = $this->accessToken;
		$response = HttpUtil::httpRequest('GET', self::$rootUrl . '/user/', $params);
		$user = json_decode($response, true);
		
		return $user;
		
	}
	
	/**
	 * Generic function to fetch a piece of content
	 * 
	 * @param string $type Required, the type of content to fetch
	 * @param arrray $query Optional, the query to send with the request
	 * @return array An array of content data
	 */
	private function getContent($type, $query) {
		
		if ($this->accessToken == null) {
			throw new AccessTokenRequiredException();
		}
		
		$params['access_token'] = $this->accessToken;
		
		if ($query != null) {
			$params = array_merge($params, $query);
		}
		
		print_r($params);
		$response = HttpUtil::httpRequest('GET', self::$rootUrl . '/content/' . $type . '/', $params);
		$results = json_decode($response, true);
		return $results;
		
	}
	
	/**
	 * Fetch a user's photos
	 * 
	 * @param arrray $query Optional, the query to send with the request
	 * @return array An array of photo data
	 */
	public function getPhotos($query = null) {
		return $this->getContent('photos', $query);
	}
	
	/**
	 * Fetch a user's checkins
	 * 
	 * @param arrray $query Optional, the query to send with the request
	 * @return array An array of checkin data
	 */
	public function getCheckins($query = null) {
		return $this->getContent('checkins', $query);
	}
	
	/**
	 * Fetch a user's status
	 * 
	 * @param arrray $query Optional, the query to send with the request
	 * @return array An array of photo data
	 */
	public function getStatus($query = null) {
		return $this->getContent('status', $query);
	}
	
}

class AccessTokenRequiredException extends Exception {}


/**
 * A simple utility to make curl get/post easier, and to decouple the code from curl if 
 * I want to add alternate support
 * 
 * @author Aaron Hedges <aaron@dashron.com>
 */
class HttpUtil {
	
	/**
	 * Posts the parameters to the provided url
	 * 
	 * @param string $url Required, url to post the params to
	 * @param array $params Required, list of parameters to post to the url
	 * @return string The body of the HTTP response
	 */
	public static function createGetUrl($url, $params) {
		
		if (isset($params)) {
			if (strstr($url, '?')) {
				$url .= '&' . http_build_query($params);
			} else {
				$url .= '?' . http_build_query($params);
			}
		}
		
		return $url;
		
	}
	
	/**
	 * Send an HTTP request using a specified method to the url with params
	 * 
	 * @param string $method Required, the HTTP method to use
	 * @param string $url Required, the url to post the params to
	 * @param array $params Required, list of parameters to send to the url
	 * @return string The body of the HTTP response
	 */
	public static function httpRequest($method, $url, $params) {
		
		$curl = curl_init();
		
		if ($method == 'GET') {
			
			if (isset($params)) {
				if (strstr($url, '?')) {
					$url .= '&' . http_build_query($params);
				} else {
					$url .= '?' . http_build_query($params);
				}
			}
			
		} elseif ($method == 'POST') {
			
			$params = http_build_query($params);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			
		}
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		$data = curl_exec($curl);
		
		if (curl_errno($curl)) {
			$err = curl_error($curl);
			curl_close($curl);
			throw new Exception($err);
		}
		
		$page = curl_getinfo($curl);
		
		if ($page['http_code']!=200) {	 
			echo($data);
		}
		
		curl_close($curl);
		
		return $data;
	}
	
	/**
	 * Builds an array out of the query string of a url
	 * name=john&id=5 becomes array('name'=>'john', 'id'=>'5')
	 * 
	 * @param string $query Required, the query string to parse
	 * @return array An array representation of the query
	 */
	public static function parseQuery($query) {
		
		$query = rawurldecode($query);
		$params = explode('&', $query);
		
		$paramArray = array();
		
		foreach ($params as $param) {
			$split = explode('=', $param);
			$paramArray[$split[0]] = $split[1];
		}
		
		return $paramArray;
		
	}
	
}