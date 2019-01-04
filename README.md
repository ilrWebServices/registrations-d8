# ILR Registrations Drupal 8 Site

This site serves as a Drupal-based version of https://register.ilr.cornell.edu, which is used to register users for classes and programs, track certificate progress, and process payments.

It is based on the [Composer template for Drupal projects][].

## Requirements

- git
- PHP 7.1 or greater
- Composer
- Drush ([Drush launcher][] is recommended, since a copy of Drush is included in this project)

## Setup

1. Clone this repository
2. Open a terminal at the root of the repo
3. Run `composer install`
4. Copy `.env.example` to `.env` and update the database connection info.

Setting up your local web server and database is left as an excercise for the developer. Please note when setting up your web server, though, that this project uses the `web` directory as the web root.

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
$ composer update drupal/core webflo/drupal-core-require-dev symfony/* --with-dependencies
```

Then run `git diff` to determine if any of the scaffolding files have changed.

Review changes and restore any customizations to `.htaccess` or `robots.txt`. Commit everything together in a single commit (or merge), so `web` will remain in sync with `core` when checking out branches or running `git bisect`.

[Composer template for Drupal projects]: https://github.com/drupal-composer/drupal-project
[Drush launcher]: https://github.com/drush-ops/drush-launcher
[composer-patches]: https://github.com/cweagans/composer-patches
