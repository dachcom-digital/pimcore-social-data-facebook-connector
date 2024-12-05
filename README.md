# Pimcore Social Data - Facebook Connector
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Software License](https://img.shields.io/badge/license-DCL-white.svg?style=flat-square&color=%23ff5c5c)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-facebook-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-facebook-connector)
[![Tests](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-facebook-connector/.github/workflows/codeception.yml?branch=master&style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-facebook-connector/.github/workflows/php-stan.yml?branch=master&style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

This Connector allows you to fetch social posts from Facebook.

![image](https://user-images.githubusercontent.com/700119/94452916-5f51cb80-01b0-11eb-86b2-026d8b7ef6f7.png)

### Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained     | Branch                                                                                    |
|---------|----------------------------|----------------------------|--------------|----------------|-------------------------------------------------------------------------------------------|
| **3.x** | `11.0`                     | `6.2`                      | 07.11.2023   | Feature Branch | master                                                                                    |
| **2.x** | `10.1` - `10.6`            | `5.4`                      | 05.01.2022   | Unsupported    | [2.x](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/tree/2.x) |
| **1.x** | `6.0` - `6.9`              | `3.4`, `^4.4`              | 22.10.2020   | Unsupported    | [1.x](https://github.com/dachcom-digital/pimcore-social-data-facebook-connector/tree/1.x) |

## Installation

```json
"require" : {
    "dachcom-digital/social-data" : "~3.1.0",
    "dachcom-digital/social-data-facebook-connector" : "~3.1.0"
}
```

Add Bundle to `bundles.php`:
```php
return [
    SocialData\Connector\Facebook\SocialDataFacebookConnectorBundle::class => ['all' => true],
];
```

### Install Assets
```bash
bin/console assets:install public --relative --symlink
```

## Enable Connector
```yaml
# config/packages/social_data.yaml
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

| Name      | Description                                                                   |
|-----------|-------------------------------------------------------------------------------|
| `Page Id` | Defines which page entries should be imported                                 |
| `Limit`   | Define a limit to restrict the amount of social posts to import (Default: 50) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# config/packages/social_data.yaml
social_data:
    available_connectors:
        -   connector_name: facebook
            connector_config:
                api_connect_permission: ['pages_show_list'] # default value
```
***

## License
**DACHCOM.DIGITAL AG**, Löwenhofstrasse 15, 9424 Rheineck, Schweiz  
[dachcom.com](https://www.dachcom.com), dcdi@dachcom.ch  
Copyright © 2024 DACHCOM.DIGITAL. All rights reserved.  

For licensing details please visit [LICENSE.md](LICENSE.md)  

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)
