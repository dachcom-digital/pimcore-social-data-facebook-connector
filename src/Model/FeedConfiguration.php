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

use SocialData\Connector\Facebook\Form\Admin\Type\FacebookFeedType;
use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    protected ?string $pageId = null;
    protected ?int $limit = null;

    public static function getFormClass(): string
    {
        return FacebookFeedType::class;
    }

    public function setPageId(?string $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getPageId(): ?string
    {
        return $this->pageId;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
