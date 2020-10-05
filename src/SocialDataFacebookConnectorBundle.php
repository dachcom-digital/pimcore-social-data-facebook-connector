<?php

namespace SocialData\Connector\Facebook;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataFacebookConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'dachcom-digital/social-data-facebook-connector';

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        return [
            '/bundles/socialdatafacebookconnector/css/admin.css'
        ];
    }

    /**
     * @return string[]
     */
    public function getJsPaths()
    {
        return [
            '/bundles/socialdatafacebookconnector/js/connector/facebook-connector.js',
            '/bundles/socialdatafacebookconnector/js/feed/facebook-feed.js',
        ];
    }
}
