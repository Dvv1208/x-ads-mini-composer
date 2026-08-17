<?php

/**
 * X Ads Mini Composer configuration.
 *
 * account_id and user_id are loaded from MySQL.
 * bearer is global; each account stores its own X Cookie in MySQL and ct0 is read from that Cookie.
 */
return [
    'api_version' => '11',

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'if0_42654253_x_ads',
        'username' => 'admin',
        'password' => 'admin',
        'charset' => 'utf8mb4',
    ],

    'schedule_after_minutes' => 1,

    'bearer' => 'AAAAAAAAAAAAAAAAAAAAAPnA9gAAAAAAZHpqKYoDdMCaqTUBktzAdK38BGk=LNsI9r2BHSjZ7cl5wD6Sh6NhxwZd2j8lXDSd6GDoQVYBlzx5Ff',

    // X session Cookie is stored per account in the `user.x_cookie` column.
    'timeout' => 30,
];
