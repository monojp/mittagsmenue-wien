<?php

namespace App\Parser;

use Psr\Log\LoggerInterface;

class FacebookService
{
    private $_accessToken;

    private $logger;

    // constructor, creates an auth token with app id and secret
    // throws an exception if authentication fails
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string|null
     */
    private function accessToken(): ?string
    {
        if ($this->_accessToken) {
            return $this->_accessToken;
        }

        $app_id = getenv('FACEBOOK_APP_ID');
        $app_secret = getenv('FACEBOOK_APP_SECRET');
        $response = $this->request("https://graph.facebook.com/oauth/access_token?grant_type=client_credentials&client_id={$app_id}&client_secret={$app_secret}");
        if (!isset($response['access_token'])) {
            $this->logger->error('access token is not set', [
                'response' => $response
            ]);
            return null;
        }

        if (empty($response['access_token'])) {
            $this->logger->error('access token is empty', [
                'response' => $response
            ]);
            return null;
        }

        return $this->_accessToken = (string)$response['access_token'];
    }

    /**
     * @param $url
     * @return mixed|null
     */
    private function request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, ini_get('default_socket_timeout'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, getenv('USER_AGENT'));

        $response = curl_exec($ch);
        if ($response === false) {
            $this->logger->error(curl_error($ch), [
                'resource' => $ch,
            ]);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        // decode json response and handle json errors
        $response = json_decode($response, true);
        if ($response === null) {
            $this->logger->error(json_last_error_msg(), [
                'response' => $response,
            ]);
            return null;
        }

        // handle api errors
        if (isset($response['error'])) {
            $this->logger->error($response['error']['message'], [
                'response' => $response,
            ]);

            return null;
        }

        return $response;
    }

    /**
     * gets all (public) facebook posts of a profile / page
     * @param int $profile_id
     * @return array
     */
    public function allPosts(int $profile_id): array
    {
        $response = $this->request("https://graph.facebook.com/{$profile_id}/feed?access_token=" . $this->accessToken());
        if (!$response || !isset($response['data']) || !is_array($response['data'])) {
            $this->logger->error('response is not valid', [
                'response' => $response
            ]);
            return [];
        }

        return $response['data'];
    }
}
