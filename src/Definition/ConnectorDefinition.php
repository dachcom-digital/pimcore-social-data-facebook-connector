<?php

namespace SocialData\Connector\Facebook\Definition;

use SocialData\Connector\Facebook\Model\EngineConfiguration;
use SocialData\Connector\Facebook\Model\FeedConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialDataBundle\Model\ConnectorEngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectorDefinition implements ConnectorDefinitionInterface
{
    protected ?ConnectorEngineInterface $connectorEngine = null;
    protected SocialPostBuilderInterface $socialPostBuilder;
    protected array $definitionConfiguration;

    public function setConnectorEngine(?ConnectorEngineInterface $connectorEngine): void
    {
        $this->connectorEngine = $connectorEngine;
    }

    public function getConnectorEngine(): ?ConnectorEngineInterface
    {
        return $this->connectorEngine;
    }

    public function setSocialPostBuilder(SocialPostBuilderInterface $builder): void
    {
        $this->socialPostBuilder = $builder;
    }

    public function getSocialPostBuilder(): SocialPostBuilderInterface
    {
        return $this->socialPostBuilder;
    }

    public function setDefinitionConfiguration(array $definitionConfiguration): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'api_connect_permission' => ['pages_show_list']
        ]);

        $resolver->setAllowedTypes('api_connect_permission', 'string[]');

        try {
            $this->definitionConfiguration = $resolver->resolve($definitionConfiguration);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf('Invalid "%s" connector configuration. %s', 'facebook', $e->getMessage()));
        }
    }

    public function getDefinitionConfiguration(): array
    {
        return $this->definitionConfiguration;
    }

    public function engineIsLoaded(): bool
    {
        return $this->getConnectorEngine() instanceof ConnectorEngineInterface;
    }

    public function isOnline(): bool
    {
        if (!$this->engineIsLoaded()) {
            return false;
        }

        if ($this->getConnectorEngine()->isEnabled() === false) {
            return false;
        }

        if (!$this->isConnected()) {
            return false;
        }

        $configuration = $this->getConnectorEngine()->getConfiguration();
        if (!$configuration instanceof ConnectorEngineConfigurationInterface) {
            return false;
        }

        return $this->isConnected();
    }

    public function beforeEnable(): void
    {
        // not required. just enable it.
    }

    public function beforeDisable(): void
    {
        // not required. just disable it.
    }

    public function isAutoConnected(): bool
    {
        return false;
    }

    public function isConnected(): bool
    {
        if ($this->engineIsLoaded() === false) {
            return false;
        }

        $configuration = $this->getConnectorEngine()->getConfiguration();
        if (!$configuration instanceof EngineConfiguration) {
            return false;
        }

        return $configuration->getAccessToken() !== null;
    }

    public function connect(): void
    {
        if ($this->isConnected() === false) {
            throw new \Exception('No valid Access Token found. If you already tried to connect your application check your credentials again.');
        }
    }

    public function disconnect(): void
    {
        // @todo
    }

    public function needsEngineConfiguration(): bool
    {
        return true;
    }

    public function getEngineConfigurationClass(): string
    {
        return EngineConfiguration::class;
    }

    public function getFeedConfigurationClass(): string
    {
        return FeedConfiguration::class;
    }

    public function getEngineConfiguration(): ?ConnectorEngineConfigurationInterface
    {
        if (!$this->engineIsLoaded()) {
            return null;
        }

        $engineConfiguration = $this->getConnectorEngine()->getConfiguration();
        if (!$engineConfiguration instanceof ConnectorEngineConfigurationInterface) {
            return null;
        }

        return $engineConfiguration;
    }
}
