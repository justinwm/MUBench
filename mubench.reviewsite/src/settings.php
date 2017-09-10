<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        'db' => [
            'driver' => 'sqlite',
            'host' => 'localhost',
            'database' => 'test.sqlite',
            'username' => 'admin',
            'password' => 'admin',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'mubench',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'site_base_url' => '/',
    ],
    // REFACTOR remove Pixie database setup after migration to Eloquent (see configuration above)
    'db' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'database',
        'username'  => 'username',
        'password'  => 'password',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => 'mubench_',
        'options'   => []
    ],
    'upload' => "./upload",
    'users' => [
        "admin" => "pass",
        "admin2" => "pass"
    ],
    'site_base_url' => '/',
    'default_ex2_review_size' => '20'
];
