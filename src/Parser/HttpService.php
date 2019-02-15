<?php

namespace App\Parser;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpService
{
    private $client;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = new Client([
            'headers' => [
                'User-Agent' => getenv('USER_AGENT'),
            ]
        ]);
    }

    /**
     * @param ResponseInterface $response
     */
    private function handleResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('Response != 200', [
                'reason' => $response->getReasonPhrase(),
            ]);
        }
    }

    /**
     * @param string $uri
     * @param array $options
     * @return string
     */
    public function getBodyContents(string $uri, array $options = []): string
    {
        $response = $this->client->get($uri, $options);
        $this->handleResponse($response);
        return $response->getBody()->getContents();
    }

}