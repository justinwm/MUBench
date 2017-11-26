<?php

/** @var \Slim\Http\Request $request */

use MuBench\ReviewSite\Models\Reviewer;
use Slim\Views\PhpRenderer;

$request = $container->request;
$serverParams = $request->getServerparams();
$user_name = array_key_exists('PHP_AUTH_USER', $serverParams) ? $serverParams['PHP_AUTH_USER'] : null;
$user = null;
if($user_name){
    $user = Reviewer::firstOrCreate(['name' => $user_name]);
}
$container['user'] = $user;

$container['renderer'] = function ($container) use ($request, $user) {
    $siteBaseURL = rtrim(str_replace('index.php', '', $container->request->getUri()->getBasePath()), '/') . '/';
    $publicURLPrefix = $siteBaseURL;
    $privateURLPrefix = $siteBaseURL . 'private/';

    $path = $request->getUri()->getPath();
    $path = htmlspecialchars($path === '/' ? '' : $path);

    $experiments = \MuBench\ReviewSite\Models\Experiment::all();
    $detectors = [];
    foreach ($experiments as $experiment) { /** @var \MuBench\ReviewSite\Models\Experiment $experiment */
        $detectors[$experiment->id] = \MuBench\ReviewSite\Models\Detector::withFindings($experiment);
    }

    $pathFor = function ($routeName, $args = [], $private = false) use ($container, $user) {
        $routeName = $user || $private ? "private.$routeName" : $routeName;
        return $container->router->pathFor($routeName, $args);
    };

    $defaultTemplateVariables = [
        'user' => $user,

        'pathFor' => $pathFor,
        'isCurrentPath' => function ($routeName, $args = []) use ($container, $pathFor) {
            $path = $container->request->getUri()->getPath();
            $checkPath = $pathFor($routeName, $args);
            return strpos($path, $checkPath) !== false;
        },
        'srcUrlFor' => function ($resourceName) use ($container, $siteBaseURL) {
            return  "$siteBaseURL$resourceName";
        },
        'loginPath' => $privateURLPrefix . substr($path, 1),

        'site_base_url' => $siteBaseURL,
        'public_url_prefix' => $publicURLPrefix,
        'private_url_prefix' => $privateURLPrefix,
        'url_prefix' => $user ? $privateURLPrefix : $publicURLPrefix,

        'path' => $path,
        'origin_param' => htmlspecialchars("?origin=$path"),
        'origin_path' => htmlspecialchars($request->getQueryParam('origin', '')),

        'experiments' => $experiments,
        'experiment' => null,
        'detectors' => $detectors,
        'detector' => null,
        'resolution_reviewer' => Reviewer::where('name' ,'resolution')->first(),

        'ex2_review_size' => $request->getQueryParam("ex2_review_size", $container["default_ex2_review_size"])
    ];

    return new PhpRenderer(__DIR__ . '/../templates/', $defaultTemplateVariables);
};