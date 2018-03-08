<?php

/*
 * This file is part of uhin/laravel_api.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    'pager_duty' => [
        'api_key'           => env('PAGER_DUTY_API_KEY', null),
        'integration_key'   => env('PAGER_DUTY_INTEGRATION_KEY', null),
        'url'               => env('PAGER_DUTY_URL', 'https://events.pagerduty.com/v2/enqueue'),
        'client'            => env('PAGER_DUTY_CLIENT', config('app.name')),
        'severity'          => env('PAGER_DUTY_SEVERITY', 'info'),
        'action'            => env('PAGER_DUTY_ACTION', 'trigger'),
    ],

    'rabbit' => [
        'host'              => env('RABBITMQ_HOST', '127.0.0.1'),
        'port'              => env('RABBITMQ_PORT', 5672),
        'username'          => env('RABBITMQ_USERNAME', null),
        'password'          => env('RABBITMQ_PASSWORD', null),
        'exchange'          => env('RABBITMQ_EXCHANGE', null),
        'queue'             => env('RABBITMQ_QUEUE', null),
        'dlx_queue'         => env('RABBITMQ_DLX_QUEUE', env('RABBITMQ_QUEUE') . '.dlx'),
        'routing_key'       => env('RABBITMQ_QUEUE_ROUTING_KEY', env('RABBITMQ_QUEUE', null)),
        'dlx_routing_key'   => env('RABBITMQ_DLX_QUEUE_ROUTING_KEY', 'dlx'),
    ],

];
