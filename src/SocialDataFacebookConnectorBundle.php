<?php

namespace SocialData\Connector\Facebook;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataFacebookConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-facebook-connector';

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/socialdatafacebookconnector/css/admin.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/socialdatafacebookconnector/js/connector/facebook-connector.js',
            '/bundles/socialdatafacebookconnector/js/feed/facebook-feed.js',
        ];
    }
}
