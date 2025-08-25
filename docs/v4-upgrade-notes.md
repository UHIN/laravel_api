# Version 4 Upgrade Documentation

[To Readme](../README.md)

## Steps to Upgrade

1.  **Follow the Laravel Upgrade Guides**: It is recommended to follow the official Laravel upgrade guides for each major version to ensure a smooth transition.
    * [Laravel 9 Upgrade Guide](https://laravel.com/docs/9.x/upgrade)
    * [Laravel 10 Upgrade Guide](https://laravel.com/docs/10.x/upgrade)
    * [Laravel 11 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
    * [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)

## Changes

### Laravel 12 Compatibility

This release introduces full compatibility with Laravel 12. The package has been updated to leverage the new, streamlined application structure and modern development workflows of the latest framework version.

### API-Centric Design

The `uhin:init` command has been enhanced to remove all front-end and web-related boilerplate, such as:

* `package.json`
* `vite.config.js`
* `routes/web.php`
* `Models/User.php`
* `bootstrap/packages.php`
* `bootstrap/services.php`
* `public/favicon.ico`
* The entire root `resources` directory
* Default database factories and seeders

Additionally, the `uhin:make:endpoint` command will no longer create seeders or factories by default, providing a cleaner, more focused experience for building a back-end API.

### Sanctum Removal

The `uhin:init` command now includes an automated process to completely remove all traces of Laravel Sanctum, including:

* The `laravel/sanctum` dependency from `composer.json`
* The `auth:sanctum` middleware from `routes/api.php`
* The default `/user` route
* The `config/sanctum.php` file

This allows you to choose your own authentication method without needing to manually clean up Sanctum's default configuration.

---

### Files Changed for v4

* `composer.json` - `laravel/framework` dependency updated to `^12.0`, `laravel/sanctum` removed.
* `UhinInit.php` - Completely refactored to first run Laravel's official `install:api` command, and then strip out all unwanted files and dependencies, including Sanctum.
* `routes/api.php` - The default `Route::get('/user', ...)` route is now removed by the `uhin:init` command.