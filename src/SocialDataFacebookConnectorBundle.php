<?php

namespace SocialData\Connector\Facebook;

use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SocialDataFacebookConnectorBundle extends Bundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-facebook-connector';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
