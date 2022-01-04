# Pimcore Social Data - Facebook Connector

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-facebook-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-facebook-connector)
[![Tests](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-social-data-facebook-connector/Codeception/master?style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-social-data-facebook-connector/PHP%20Stan/master?style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

This Connector allows you to fetch social posts from Facebook.

![image](https://user-images.githubusercontent.com/700119/94452916-5f51cb80-01b0-11eb-86b2-026d8b7ef6f7.png)

### Release Plan
| Release | Supported Pimcore Versions        | Supported Symfony Versions | Release Date | Maintained     | Branch     |
|---------|-----------------------------------|----------------------------|--------------|----------------|------------|
| **2.x** | `10.1` - `10.2`                   | `5.4`                      | --           | Feature Branch | master     |
| **1.x** | `6.0` - `6.9`                     | `3.4`, `^4.4`              | 22.10.2020   | Unsupported    | 1.x        |

## Installation

### I. Add Dependency
```json
"require" : {
    "dachcom-digital/social-data" : "~2.0.0",
    "dachcom-digital/social-data-facebook-connector" : "~2.0.0",
}
```

### II. Register Connector Bundle
```php
// src/Kernel.php
namespace App;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class Kernel extends \Pimcore\Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new SocialData\Connector\Facebook\SocialDataFacebookConnectorBundle());
    }
}
```

### III. Install Assets
```bash
bin/console assets:install public --relative --symlink
```

## Enable Connector
```yaml
# app/config/config.yml
social_data:
    social_post_data_class: SocialPost
    available_connectors:
        -   connector_name: facebook
```

### Set Cookie SameSite to Lax
Otherwise, the oauth connection won't work.
> If you have any hints to allow processing an oauth connection within `strict` mode, 
> please [tell us](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/issues).

```yaml
framework:
    session:
        cookie_samesite: 'lax'
```

## Facebook Backoffice
First, you need to create a facebook app.
Add `https://YOURDOMAIN/admin/social-data/connector/facebook/check` to the `Valid OAuth Redirect URIs` in facebook backoffice.

## Connector Configuration
![image](https://user-images.githubusercontent.com/700119/94451768-164d4780-01af-11eb-9e52-3132ea02d714.png)

Now head back to the backend (`System` => `Social Data` => `Connector Configuration`) and checkout the facebook tab.
- Click on `Install`
- Click on `Enable`
- Before you hit the `Connect` button, you need to fill you out the Connector Configuration. After that, click "Save".
- Click `Connect`
  
## Connection
![image](https://user-images.githubusercontent.com/700119/95068621-d1249a80-0705-11eb-8ebb-b3b15e5e832f.png)

This will guide you through the facebook token generation. 
After hitting the "Connect" button, **a popup** will open to guide you through facebook authentication process. 
If everything worked out fine, the connection setup is complete after the popup closes.
Otherwise, you'll receive an error message. You may then need to repeat the connection step.

## Feed Configuration

| Name | Description
|------|----------------------|
| `Page Id` | Defines which page entries should be imported |
| `Limit` | Define a limit to restrict the amount of social posts to import (Default: 50) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# app/config/config.yml
social_data:
    available_connectors:
        -   connector_name: facebook
            connector_config:
                api_connect_permission: ['pages_show_list'] # default value
```

## Third-Party Requirements
To use this connector, this bundle requires some additional packages which will be also installed with this bundle:
- [league/oauth2-facebook](https://github.com/thephpleague/oauth2-facebook)

***

## Copyright and license
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)  

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)
