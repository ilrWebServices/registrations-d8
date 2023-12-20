# ILR Registrations Drupal 8 Site

This site serves as a Drupal-based version of https://register.ilr.cornell.edu, which is used to register users for classes and programs, track certificate progress, and process payments.

It is based on the [Composer template for Drupal projects][].

## Requirements

- git
- PHP 8.1 or greater
- Composer
- Drush ([Drush launcher][] is recommended, since a copy of Drush is included in this project)
- Node.js 8.x or greater

## Setup

1. Clone this repository
2. Open a terminal at the root of the repo
3. Run `composer install`
4. Copy `.env.example` to `.env` and update the database connection, salesforce, and payment info.
5. Run `npm install && npm run build` to install the Union library and generate the CSS for the custom theme.

Setting up your local web server and database is left as an excercise for the developer. Please note when setting up your web server, though, that this project uses the `web` directory as the web root.

### Development-only Settings

You may wish to configure some settings (cache, config splits, etc.) for local development. To do so, you may optionally add a `settings.local.php` file to `web/sites/default/`.

Here's a suggested example:

```
<?php

// Allow any domain to access the site.
$settings['trusted_host_patterns'] = array();

// Switch the salesforce auth provider for production. Otherwise, we will use
// the default for dev.
// $config['salesforce.settings']['salesforce_auth_provider'] = 'ilr_marketing_jwt_oauth';

// Enable the config split for development-only modules, like field_ui.
$config['config_split.config_split.dev']['status'] = TRUE;

// Enable local development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/local_development.services.yml';

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Show more cron logging info, including in `drush cron`.
$config['system.cron']['logging'] = TRUE;

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Skip file system permissions hardening.
$settings['skip_permissions_hardening'] = TRUE;

// Config ignore pattern debugging.
$settings['config_ignore_pattern_debug'] = FALSE;
```

### Clean install

To work on a blank slate of the codebase without syncing content and data from production, install Drupal like so:

```
$ drush si minimal --config-dir=../config/sync
```

## Adding and Updating Modules and Other Dependencies

Use standard composer commands to add, remove, and update project dependencies. To add the rules module, for example, run:

```
$ composer require drupal/rules:~1.0
```

To add a module for developer use only, which will prevent its installation on the production site, use the `--dev` paramater, like so:

```
$ composer require --dev drupal/devel:~1.0
```

To update a module, run:

```
$ composer update --with-dependencies "drupal/MODULNAME"
```

[Commerce is special][], though. Note the asterisk at the end of the module name:

```
$ composer update --with-dependencies "drupal/commerce*"
```

## Patching Contributed modules

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches][] plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```

## Updating Drupal core

```
$ composer update "drupal/core-*" --with-all-dependencies
```

Then run `git diff` to determine if any of the scaffolding files have changed.

Review changes and restore any customizations to `.htaccess` or `robots.txt`. Commit everything together in a single commit (or merge), so `web` will remain in sync with `core` when checking out branches or running `git bisect`.

## Updating Commerce

```
$ composer update "drupal/commerce*" --with-all-dependencies
```

## Salesforce Integration

This site uses the Salesforce Suite module to synchronize some Salesforce objects to Drupal entities, mainly Professionional Programs courses, classes, and related items.

Authentication is done via OAuth JWT tokens - one for ILR Drupal sites to connect to the production instance and one for all development sites to connect to the `tiger` sandbox instance. See the [OAuth JWT Bearer Token flow documentation][] for more information.

### Configuration

The only required configuration is to set the `SALESFORCE_CONSUMER_JWT_X509_KEY` environment variable. For development, this is done by editing the `.env` file. On production, this is done via platform.sh environment variable settings.

The JWT x509 key is stored in the 'SalesForce prod key/secret for ILR Marketing D8 JWT' note in the shared 'ILR Webdev' folder in LastPass.

Some configuration, mainly registration and participant types, is ignored from sync. The `config_ignore_pattern` module is used. See $settings['config_ignore_patterns'].

### Usage

You can see the status of the two authentication providers via drush:

```
$ drush sflp
```

...or by visiting `/admin/config/salesforce/authorize/list`.

You can then refresh the authentication tokens for one or both of the providers by either using the _Edit / Re-auth_ button in the web interface or via drush:

```
$ drush sfrt ilr_marketing_jwt_oauth_dev
```

If needed, the default provider can be overriden during local development (e.g. for testing with production data) by updating the configuration for the `salesforce_auth_provider`. See the "Development-only Settings" above for an example.

## Theme Development

This project uses a custom theme that includes shared components from the [Union Component Library][].

The custom theme is found in `web/themes/custom/union_register/`. The Sass CSS preprocessor is used for styles, and you can compile CSS either 1) manually via `npm run build` or 2) automatically by running `npm start` in a spare terminal.

### Including Union Components

Union Components are integrated into the theme using the [Union Organizer][] module. See the documentation for that module for more information.

### Livereload

If you set `LIVERELOAD=1` in your `.env` file and reload your browser while `npm start` is running, changes to stylesheets will reload automatically in your browser.


[Composer template for Drupal projects]: https://github.com/drupal-composer/drupal-project
[Drush launcher]: https://github.com/drush-ops/drush-launcher
[git submodules]: https://git-scm.com/book/en/v2/Git-Tools-Submodules
[OAuth JWT Bearer Token flow documentation]: https://www.drupal.org/docs/8/modules/salesforce-suite/create-a-oauth-jwt-bearer-token-flow-connected-app-4x
[Commerce is special]: https://docs.drupalcommerce.org/commerce2/developer-guide/install-update/updating
[composer-patches]: https://github.com/cweagans/composer-patches
[Union Component Library]: https://github.com/ilrWebServices/union
[Union Organizer]: https://github.com/ilrWebServices/union_organizer
