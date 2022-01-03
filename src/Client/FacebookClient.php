<?php

namespace SocialData\Connector\Facebook\Client;

use League\OAuth2\Client\Provider\Facebook;
use SocialData\Connector\Facebook\Model\EngineConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FacebookClient
{
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
            'graphApiVersion' => 'v2.10',
        ]);
    }

    protected function generateRedirectUri(): string
    {
        return $this->router->generate('social_data_connector_facebook_connect_check', UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
