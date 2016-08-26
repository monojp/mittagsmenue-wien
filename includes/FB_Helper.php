<?php

class FB_Helper {
	private $auth_token = null;

	// constructor, creates an auth token with app id and secret
	// throws an exception if authentication fails
	public function __construct($app_id, $app_secret) {
		$response = $this->fetchUrl("https://graph.facebook.com/oauth/access_token?grant_type=client_credentials&client_id={$app_id}&client_secret={$app_secret}");
		$response_decoded = json_decode($this->auth_token, true);
		if ($response && isset($response['error'])) {
			throw new Exception($response['error']['message'], $response['error']['code']);
		}

		$this->auth_token = $response;
	}

	// curl fetch helper function
	private function fetchUrl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0');

		$feedData = curl_exec($ch);
		curl_close($ch);

		return $feedData;
	}

	// gets all (public) facebook posts of a profile / page
	// returns null if something went wrong
	public function get_all_posts($profile_id) {
		$response = $this->fetchUrl("https://graph.facebook.com/{$profile_id}/feed?{$this->auth_token}");
		$response = json_decode($response, true);
		if (!$response || !isset($response['data'])) {
			return null;
		}

		return is_array($response['data']) ? $response['data'] : null;
	}

	// gets all (public) facebook posts of a profile / page with pictures
	// returns null if something went wrong
	public function get_all_picture_posts($profile_id) {
		$posts = $this->get_all_posts($profile_id);
		if (!$posts) {
			return null;
		}

		$posts_return = [];
		foreach ((array)$posts as $post) {
			// ignore posts with missing data
			if (!isset($post['message']) || !isset($post['created_time']) || !isset($post['from'])
					|| !isset($post['picture']) || !isset($post['object_id'])) {
				continue;
			}

			// ignore posts other than from the wanted page
			if ($post['from']['id'] != $profile_id) {
				continue;
			}

			$posts_return[] = $post;
		}
		return $posts_return;
	}

	public function get_graph_object($object_id) {
		return "https://graph.facebook.com/{$object_id}?{$this->auth_token}";
	}

	public function get_picture_url($object_id) {
		// get image date from graph object
		$images = json_decode($this->fetchUrl($this->get_graph_object($object_id)), true);
		if ($images === null || empty($images['images'])) {
			return null;
		}
		// use the first image which should be the biggest
		return reset($images['images'])['source'];
	}
}
