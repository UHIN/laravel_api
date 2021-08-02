# Version 3 Upgrade Documentation

[To Readme](../README.md)

## Steps to Upgrade

1. Follow the Laravel Upgrade guide to Laravel v6: https://laravel.com/docs/6.x/upgrade
2. Follow the Laravel Upgrade guide to Laravel v7: https://laravel.com/docs/7.x/upgrade
3. Follow the Laravel Upgrade guide to Laravel v8: https://laravel.com/docs/8.x/upgrade
4. Replace the exception handler in app/Exceptions/Handler.php with current version
5. All Controllers need the namespace updated for the UhinApi (see File Changes below)
6. All resources need the class names changed from Resource to JsonResource
7. Routes syntax need to be updated. The Laravel upgrade guide states this is optional, but it is not compatible with new projects out of the box, so the uhin laravel api has been updated to use the new syntax.
8. All references to the Rabbit libraries need to be updated to reflect the new namespace (see File Changes below)
9. All references to full text search, sendgrid and pagerduty need to be updated to reflect new namespaces (see File Changes below)
10. Make sure that any env settings (check prod.env, uat.env, and dev.env) that have a url to another service do not have a trailing slash. For example, references to 'https://trading-partner-service.peks.uhin.org/' should be changed to 'https://trading-partner-service.peks.uhin.org'.
11. If the service uses Rabbit, you will need to add a new environment var and config value:
    - Add `'ssl' => env('RABBIT_SSL', false),` to the `config/uhin.php` file inside of the `rabbit` section.
    - Add `RABBIT_SSL=false` to the following files:
        - `.env`
        - `.env.example`
        - `deploy/dev/dev.env`
        - `deploy/uat/uat.env`
        - `deploy/prod/prod.env`
    - NOTE: If your service does use SSL for Rabbit, make sure to set all of the values above to `true` instead of `false`.
12. Laravel will no longer accept `1` and `true` as equivalent values during validation. Any validators that use this assumption will need to explicitly use `true`. The following regex should be able to uncover any of the potential validators that need to be changed: `[a-zA-Z_]+,[0,1]`
13. The default date format for Resources/Models has changed which will potentially break DB writes and the api-gateway's deserializer, so we need to override the `serializeDate` function in every model of the service being upgraded. Add this function to every Model in the service:
    ```php
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    ```

## Changes

### Routes
Laravel 8 changed how the routes file is structured. You will need to update all of your routes. Also, remove the `$namespace` property from your `RouteServiceProvider` class.

See https://laravel.com/docs/8.x/upgrade#routing for more info

### Namespaces
There were several breaking changes to Laravel 8 which is the minimum Larvael version for v3. The biggest changes are updated namespaces. Laravel 8 likes PSR4 namespaces which requires that the Namespace follows the folder structure. To better organize the Rabbit builder, connection manager, receiver and sender have been moved into their own folder.

### Files Changed for v3
Make sure to double check if the project uses any of these files/classes and update any namespaces and file locations accordingly.

- `composer.json` - Updated minimum `laravel/framework` version to 8 and `ramsey/uuid` to match the version used by Laravel 8
- `BaseCommand.php` - Updated namespace
- `MakeEndpoint.php` - Updated namespace, Models are now stored in `app/Models` by defualt, command updated to reflect that change, seeders have been moved to `database/seeders` folder command updated to reflect this change. Routes now require an import and functionality has been added to add the import automatically.
- `MakeRabbitBuilder.php` - Updated namespace
- `MakeWorker.php` - Updated namespace
- `UhinInit.php` - Updated namespace, Updated command to create an empty web.php routes file as it was breaking some functions.
- `WorkerDebug.php` - Updated namespace
- `WorkerDrain.php` - Updated namespace
- `WorkerStart.php` - Updated namespace
- `WorkerStop.php` - Updated namespace
- `controller.stub` - Updated namespace
- `endpoint-routes.stub` - Updated to use new Laravel routes syntax
- `rabbit-builder.stub` - Updated referenced namespaces
- `resource.stub` - As of Laravel 7 Resource was updated to be a JsonResource, referenced classes have been updated. The comment block was breaking the stub generation so they were removed.
- `worker-rabbit.stub` - Updated referenced namespaces
- `FullTextSearchable.php` - Updated namespace
- `Handler.php` - Updated to match the interface from Laravel 7+ to accept a Throwable rather than an Exception
- `PagerDuty.php` - Update namespace
- `SendGridTemplate.php` - Update namespace
- `UhinApi.php` - Update namespace
- `UhinServiceProvider.php` - Updated referenced namespaces
- `RabbitBuilder.php` - Moved to new folder and changed namespace
- `RabbitConnectionManager.php` - Moved to new folder and changed namespace
- `RabbitReceiver.php` - Moved to new folder and changed namespace
- `RabbitSender.php` - Moved to new folder and changed namespace
