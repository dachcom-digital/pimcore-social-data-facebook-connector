<?php

namespace SocialData\Connector\Facebook\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Facebook;
use SocialData\Connector\Facebook\Model\EngineConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FacebookClient
{
    protected const GRAPH_VERSION = 'v12.0';

    protected RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getClient(EngineConfiguration $configuration): Facebook
    {
        return new Facebook([
            'clientId'        => $configuration->getAppId(),
            'clientSecret'    => $configuration->getAppSecret(),
            'redirectUri'     => $this->generateRedirectUri(),
            'graphApiVersion' => self::GRAPH_VERSION,
        ]);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function makeCall(string $endpoint, string $method, EngineConfiguration $configuration, array $queryParams = [], array $formParams = []): array
    {
        $client = $this->getGuzzleClient();

        $params = [
            'query' => array_merge([
                'access_token'    => $configuration->getAccessToken(),
                'appsecret_proof' => hash_hmac('sha256', $configuration->getAccessToken(), $configuration->getAppSecret()),
            ], $queryParams)
        ];

        if (count($formParams) > 0) {
            $params['form_params'] = $formParams;
        }

        $response = $client->request($method, $endpoint, $params);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function makeGraphCall(string $query, EngineConfiguration $configuration): array
    {
        $client = $this->getGuzzleClient();

        $endpoint = sprintf(
            '%s&access_token=%s&appsecret_proof=%s',
            $query,
            $configuration->getAccessToken(),
            hash_hmac('sha256', $configuration->getAccessToken(), $configuration->getAppSecret())
        );

        return json_decode($client->get($endpoint)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function generateRedirectUri(): string
    {
        return $this->router->generate('social_data_connector_facebook_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function getGuzzleClient(): Client
    {
        return new Client([
            'base_uri' => sprintf('https://graph.facebook.com/%s', self::GRAPH_VERSION)
        ]);
    }

}
