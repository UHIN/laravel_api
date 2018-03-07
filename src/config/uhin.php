<?php

/*
 * This file is part of uhin/laravel_api.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    'pager_duty' => [
        'url'               => env('PAGER_DUTY_URL', 'https://events.pagerduty.com/v2/enqueue'),
        'client'            => env('PAGER_DUTY_CLIENT', config('app.name')),
        'severity'          => env('PAGER_DUTY_SEVERITY', 'info'),
        'action'            => env('PAGER_DUTY_ACTION', 'trigger'),
        'api_key'           => env('PAGER_DUTY_API_KEY', null),
        'integration_key'   => env('PAGER_DUTY_INTEGRATION_KEY', null),
    ],

];
