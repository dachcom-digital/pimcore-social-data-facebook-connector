# Upgrade Notes

## Migrating from Version 1.x to Version 2.0.0

### Global Changes
- PHP8 return type declarations added: you may have to adjust your extensions accordingly
- Library `facebookarchive/php-graph-sdk` has been removed, we're now using the `league/oauth2-facebook` package.