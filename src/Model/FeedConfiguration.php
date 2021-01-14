<?php

namespace SocialData\Connector\Facebook\Model;

use SocialData\Connector\Facebook\Form\Admin\Type\FacebookFeedType;
use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    /**
     * @var string|null
     */
    protected $pageId;

    /**
     * @var int
     */
    protected $limit;

    /**
     * {@inheritdoc}
     */
    public static function getFormClass()
    {
        return FacebookFeedType::class;
    }

    /**
     * @param string|null $pageId
     */
    public function setPageId(?string $pageId)
    {
        $this->pageId = $pageId;
    }

    /**
     * @return string|null
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
