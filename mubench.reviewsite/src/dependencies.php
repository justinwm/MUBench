<?php

use Interop\Container\ContainerInterface;
use MuBench\ReviewSite\DBConnection;
use MuBench\ReviewSite\Error;
use MuBench\ReviewSite\Models\Reviewer;
use Slim\Views\PhpRenderer;

$container = $app->getContainer();

$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $formatter = new \Monolog\Formatter\LineFormatter();
    $formatter->includeStacktraces();
    $handler = new Monolog\Handler\RotatingFileHandler($settings['path'], 7, $settings['level']);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
    return $logger;
};

$container['errorHandler'] = function (ContainerInterface $c) {
    return new Error($c['logger'], $c->get('settings')['settings']['displayErrorDetails']);
};

// REFACTOR delete Pixie as soon as everything is migrated to Eloquent
$container['database'] = function (ContainerInterface $c) {
    $settings = $c['settings']['db'];
    $logger = $c->get('logger');
    return new DBConnection(new \Pixie\Connection($settings['driver'], $settings), $logger);
};


$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['database2'] = $capsule;
$container['schema'] = $capsule->schema();
// The schema accesses the database through the app, which we do not have in
// this context. Therefore, use an array to provide the database. This seems
// to work fine.
/** @noinspection PhpParamsInspection */
\Illuminate\Support\Facades\Schema::setFacadeApplication(["db" => $capsule]);

$container['renderer'] = function ($container) {
    /** @var \Slim\Http\Request $request */
    $request = $container->request;
    $serverParams = $request->getServerparams();

    $user_name = array_key_exists('PHP_AUTH_USER', $serverParams) ? $serverParams['PHP_AUTH_USER'] : null;
    $user = Reviewer::where('name', $user_name)->first();

    $siteBaseURL = rtrim(str_replace('index.php', '', $container->request->getUri()->getBasePath()), '/') . '/';
    $publicURLPrefix = $siteBaseURL . 'index.php/';
    $privateURLPrefix = $siteBaseURL . 'index.php/private/';

    $path = $request->getUri()->getPath();
    $path = htmlspecialchars($path === '/' ? '' : $path);

    $experiments = \MuBench\ReviewSite\Models\Experiment::all();
    $detectors = [];
    foreach ($experiments as $experiment) { /** @var \MuBench\ReviewSite\Models\Experiment $experiment */
        $detectors[$experiment->id] = \MuBench\ReviewSite\Models\Detector::withFindings($experiment);
    }

    $pathFor = function ($routeName, $args = [], $private = false) use ($container, $user) {
        $routeName = $user || $private ? "private.$routeName" : $routeName;
        $path = $container->router->pathFor($routeName, $args);
        if (strpos($path, '/index.php') === false) {
            $path = '/index.php' . $path;
        }
        return $path;
    };

    $defaultTemplateVariables = [
        'user' => $user,

        'pathFor' => $pathFor,
        'isCurrentPath' => function ($routeName, $args = []) use ($container, $pathFor) {
            $path = $container->request->getUri()->getPath();
            if (strpos($path, '/index.php') === false) {
                $path = '/index.php/' . $path;
            }
            $checkPath = $pathFor($routeName, $args);
            return strpos($path, $checkPath) !== false;
        },
        'srcUrlFor' => function ($resourceName) use ($container, $siteBaseURL) {
            return  "$siteBaseURL$resourceName";
        },
        'loginPath' => $privateURLPrefix . $path,

        'site_base_url' => $siteBaseURL,
        'public_url_prefix' => $publicURLPrefix,
        'private_url_prefix' => $privateURLPrefix,
        'api_url_prefix' => $siteBaseURL . 'index.php/api/',
        'uploads_url_prefix' => $siteBaseURL . 'uploads/',
        'url_prefix' => $user ? $privateURLPrefix : $publicURLPrefix,

        'path' => $path,
        'origin_param' => htmlspecialchars("?origin=$path"),
        'origin_path' => htmlspecialchars($request->getQueryParam('origin', '')),

        'experiments' => $experiments,
        'experiment' => null,
        'detectors' => $detectors,
        'detector' => null,

        'ex2_review_size' => $request->getQueryParam("ex2_review_size", $container["default_ex2_review_size"])
    ];

    return new PhpRenderer(__DIR__ . '/../templates/', $defaultTemplateVariables);
};
