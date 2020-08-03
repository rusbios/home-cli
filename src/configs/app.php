<?php

return [
    'name' => 'Cli',

    'cli' => [
        'name' => 'Vasya',
    ],

    'telegram' => [
        'token' => null,
        'accessPas' => 'admin',
    ],

    'queue' => [

        'db' => [
            'type' => 'mysql',
            'host' => 'localhost',
            'dbname' => '',
            'user' => '',
            'password' => '',
            'port' => 3306,
        ],

        'ftp' => [
            'host' => 'localhost',
            'user' => '',
            'password' => '',
            'port' => 21,
            'ssl' => false
        ]
    ],
];