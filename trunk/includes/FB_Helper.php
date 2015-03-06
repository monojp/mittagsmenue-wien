<?php

class FB_Helper {
	private $auth_token = null;

	// constructor, creates an auth token with app id and secret
	// throws an exception if authentication fails
	public function __construct($app_id, $app_secret) {
		$response = $this->fetchUrl("https://graph.facebook.com/oauth/access_token?grant_type=client_credentials&client_id={$app_id}&client_secret={$app_secret}");
		$response_decoded = json_decode($this->auth_token, true);
		if ($response && isset($response['error']))
			throw new Exception($response['error']['message'], $response['error']['code']);

		$this->auth_token = $response;
	}

	// curl fetch helper function
	private function fetchUrl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

		$feedData = curl_exec($ch);
		curl_close($ch);

		return $feedData;
	}

	// gets all (public) facebook posts of a profile / page
	// returns null if something went wrong
	public function get_all_posts($profile_id) {
		$response = $this->fetchUrl("https://graph.facebook.com/{$profile_id}/feed?{$this->auth_token}");
		$response = json_decode($response, true);
		if (!$response || !isset($response['data']))
			return null;

		return is_array($response['data']) ? $response['data'] : null;
	}
}
