<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\Facebook\Model;

use SocialData\Connector\Facebook\Form\Admin\Type\FacebookEngineType;
use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;

class EngineConfiguration implements ConnectorEngineConfigurationInterface
{
    /**
     * @internal
     */
    protected ?string $accessToken = null;

    /**
     * @internal
     */
    protected ?\DateTime $accessTokenExpiresAt = null;

    protected ?string $appId;
    protected ?string $appSecret;
    protected array $pages = [];

    public static function getFormClass(): string
    {
        return FacebookEngineType::class;
    }

    public function setAccessToken(?string $token, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($token === null && $forceUpdate === false) {
            return;
        }

        $this->accessToken = $token;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessTokenExpiresAt(?\DateTime $expiresAt, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($expiresAt === null && $forceUpdate === false) {
            return;
        }

        $this->accessTokenExpiresAt = $expiresAt;
    }

    public function getAccessTokenExpiresAt(): ?\DateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppSecret(string $appSecret): void
    {
        $this->appSecret = $appSecret;
    }

    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }

    public function setPages(?array $pages): void
    {
        if (!is_array($pages)) {
            return;
        }

        $this->pages = $pages;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function hasPages(): bool
    {
        return count($this->pages) > 0;
    }

    public function getPageConfig($pageId, string $config)
    {
        if ($this->hasPages() === false) {
            return false;
        }

        foreach ($this->getPages() as $page) {
            if ($page['id'] === $pageId) {
                return $page[$config] ?? null;
            }
        }

        return null;
    }
}
