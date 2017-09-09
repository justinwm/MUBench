<?php

use Interop\Container\ContainerInterface;
use MuBench\ReviewSite\DBConnection;
use MuBench\ReviewSite\Error;
use MuBench\ReviewSite\Model\Experiment;
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

$container['database'] = function (ContainerInterface $c) {
    $settings = $c->get('db');
    $logger = $c->get('logger');
    return new DBConnection(new \Pixie\Connection($settings['driver'], $settings), $logger);
};

$container['renderer'] = function ($container) {
    /** @var \Slim\Http\Request $request */
    $request = $container->request;
    $serverParams = $request->getServerparams();

    $user = array_key_exists('PHP_AUTH_USER', $serverParams) ? $serverParams['PHP_AUTH_USER'] : null;

    $siteBaseURL = htmlspecialchars($container->settings['site_base_url']);
    $publicURLPrefix = $siteBaseURL . 'index.php';
    $privateURLPrefix = $siteBaseURL . 'index.php/private/';

    $path = $request->getUri()->getPath();
    $path = htmlspecialchars($path === '/' ? '' : $path);

    $experiments = Experiment::all();
    $detectors = [];
    foreach ($experiments as $experiment) { /** @var Experiment $experiment */
        $detectors[$experiment->getId()] = $container->database->getDetectors($experiment->getId());
    }

    $defaultTemplateVariables = [
        'user' => $user,

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
        'detectors' => $detectors,
    ];

    return new PhpRenderer(__DIR__ . '/../templates/', $defaultTemplateVariables);
};
