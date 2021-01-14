<?php

namespace SocialData\Connector\Facebook\Model;

use SocialData\Connector\Facebook\Form\Admin\Type\FacebookEngineType;
use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;

class EngineConfiguration implements ConnectorEngineConfigurationInterface
{
    /**
     * @var string
     *
     * @internal
     */
    protected $accessToken;

    /**
     * @var null|\DateTime
     *
     * @internal
     */
    protected $accessTokenExpiresAt;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appSecret;

    /**
     * {@inheritdoc}
     */
    public static function getFormClass()
    {
        return FacebookEngineType::class;
    }

    /**
     * @param string $token
     * @param bool   $forceUpdate
     */
    public function setAccessToken($token, $forceUpdate = false)
    {
        // symfony: if there are any fields on the form that aren’t included in the submitted data,
        // those fields will be explicitly set to null.
        if ($token === null && $forceUpdate === false) {
            return;
        }

        $this->accessToken = $token;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param null|\DateTime $expiresAt
     * @param bool           $forceUpdate
     */
    public function setAccessTokenExpiresAt($expiresAt, $forceUpdate = false)
    {
        // symfony: if there are any fields on the form that aren’t included in the submitted data,
        // those fields will be explicitly set to null.
        if ($expiresAt === null && $forceUpdate === false) {
            return;
        }

        $this->accessTokenExpiresAt = $expiresAt;
    }

    /**
     * @return null|\DateTime
     */
    public function getAccessTokenExpiresAt()
    {
        return $this->accessTokenExpiresAt;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $appSecret
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }
}
