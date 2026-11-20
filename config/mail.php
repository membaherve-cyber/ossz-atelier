<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| OSSZ ATELIER — mail configuration
|--------------------------------------------------------------------------
|
| The application sends one kind of e-mail: the automated daily
| business-health report (atelier:daily-report). The mailer is fully
| controlled from .env:
|
|  MAIL_MAILER=sendmail  shared hosting (Namecheap LiteSpeed) — uses the
|                        server's sendmail binary, zero credentials.
|                        RECOMMENDED for Stellar shared plans.
|  MAIL_MAILER=smtp      authenticated SMTP through a mailbox on the
|                        domain (mail.osszcollection.com, port 465 SSL) —
|                        the most reliable deliverability.
|  MAIL_MAILER=log       development — writes the rendered e-mail to
|                        storage/logs/laravel.log without sending.
|
| See DEPLOYMENT.md §6 for the recommended Namecheap setup.
|
*/

return [

    'default' => env('MAIL_MAILER', 'sendmail'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'atelier@osszcollection.com'),
        'name' => env('MAIL_FROM_NAME', 'OSSZ ATELIER'),
    ],

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'mail.osszcollection.com'),
            'port' => env('MAIL_PORT', 465),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "To" address (development only)
    |--------------------------------------------------------------------------
    */

    'to' => [
        // 'address' => 'dev@example.com',
        // 'name' => 'Local development',
    ],

];
