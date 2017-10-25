<?php
/** @var \Slim\App $app */

use MuBench\ReviewSite\Controller\DownloadController;
use MuBench\ReviewSite\Controller\FindingsUploader;
use MuBench\ReviewSite\Controller\MetadataController;
use MuBench\ReviewSite\Controller\MisuseTagsController;
use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Controller\SnippetUploader;
use MuBench\ReviewSite\DirectoryHelper;
use MuBench\ReviewSite\RoutesHelper;
use Slim\Http\Request;
use Slim\Http\Response;

require_once "route_utils.php";

$logger = $app->getContainer()['logger'];
$database = $app->getContainer()['database'];
$renderer = $app->getContainer()['renderer'];
// REFACTOR rename RoutesHelper to ResultsViewController
$routesHelper = new RoutesHelper($database, $renderer, $logger, $settings['upload'], $settings['site_base_url'], $settings['default_ex2_review_size']);
$metadataController = new MetadataController($database, $logger);



$app->get('/', \MuBench\ReviewSite\Controller\ExperimentsController::class.":index")->setName('/');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('experiment.detector');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")
    ->setName('view');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}/reviewer/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")
    ->setName('review');
$app->get('/reviews', \MuBench\ReviewSite\Controller\StatsController::class.":getResults")->setName('stats.results');
$app->get('/experiments/{experiment_id}/reviews.csv', \MuBench\ReviewSite\Controller\StatsController::class.":downloadResults")->setName('stats.results.csv');
$app->get('/tags', \MuBench\ReviewSite\Controller\StatsController::class.":getTags")->setName('stats.tags');
$app->get('/types', \MuBench\ReviewSite\Controller\StatsController::class.":getTypes")->setName('stats.types');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('download.runs');

$app->group('/private', function () use ($app, $routesHelper, $database) {
    $app->get('/', \MuBench\ReviewSite\Controller\ExperimentsController::class.":index")->setName('private./');
    $app->get('/reviews', \MuBench\ReviewSite\Controller\StatsController::class.":getResults")->setName('private.stats.results');
    $app->get('/experiments/{experiment_id}/reviews.csv', \MuBench\ReviewSite\Controller\StatsController::class.":downloadResults")->setName('private.stats.results.csv');
    $app->get('/tags', \MuBench\ReviewSite\Controller\StatsController::class.":getTags")->setName('private.stats.tags');
    $app->get('/types', \MuBench\ReviewSite\Controller\StatsController::class.":getTypes")->setName('private.stats.types');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('private.experiment.detector');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")
        ->setName('private.view');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}/reviewer/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('private.review');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/open', \MuBench\ReviewSite\Controller\ReviewController::class.":getTodo")->setName('private.todo');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/closed', \MuBench\ReviewSite\Controller\ReviewController::class.":getOverview")->setName('private.overview');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('private.download.runs');
})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));

$app->group('/api/upload', function () use ($app, $settings, $database, $metadataController) {
    $app->post('/[{experiment:ex[1-3]}]',
        function (Request $request, Response $response, array $args) use ($settings, $database) {
            $experiment = $args['experiment'];
            $run = decodeJsonBody($request);
            if (!$run) {
                return error_response($response, $this->logger, 400, "empty: " . print_r($_POST, true));
            }
            $detector = $run->{'detector'};
            if (!$detector) {
                return error_response($response, $this->logger, 400, "no detector: " . print_r($run, true));
            }
            $project = $run->{'project'};
            if (!$project) {
                return error_response($response, $this->logger, 400, "no project: " . print_r($run, true));
            }
            $version = $run->{'version'};
            if (!$version) {
                return error_response($response, $this->logger, 400, "no version: " . print_r($run, true));
            }
            $hits = $run->{'potential_hits'};
            $this->logger->info("received data for '" . $experiment . "', '" . $project . "." . $version . "' with " . count($hits) . " potential hits.");

            $uploader = new FindingsUploader($database, $this->logger);
            $uploader->processData($experiment, $run);
            $files = $request->getUploadedFiles();
            $this->logger->info("received " . count($files) . " files");
            if ($files) {
                $directoryHelper = new DirectoryHelper($settings['upload'], $this->logger);
                foreach ($files as $img) {
                    $directoryHelper->handleImage($experiment, $detector, $project, $version, $img);
                }
            }
            return $response->withStatus(200);
        });

    // REFACTOR migrate to /metadata/{project}/{version}/{misuse}/update
    $app->post('/metadata', [$metadataController, "update"]);
    // REFACTOR migrate to /reviews/{exp}/{detector}/{project}/{version}/{misuse}/{reviewerName}/update
    $app->post('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}/reviewer/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":review")->setName('private.update.review');

    // REFACTOR migrate this route to /tags/{exp}/{detector}/{project}/{version}/{misuse}/{tagname}/add
    $app->post('/tag',\MuBench\ReviewSite\Controller\TagController::class.":add");
    // REFACTOR migrate this route to /tags/{exp}/{detector}/{project}/{version}/{misuse}/{tagname}/delete
    $app->post('/delete/tag', \MuBench\ReviewSite\Controller\TagController::class.":remove");

    $app->post('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}/snippet',
        \MuBench\ReviewSite\Controller\SnippetController::class.":add")->setName('private.snippet.add');
    $app->post('/experiments/{experiment_id}/detectors/{detector_id}/project/{project_id}/version/{version_id}/misuse/{misuse_id}/snippet/{snippet_id}',
        \MuBench\ReviewSite\Controller\SnippetController::class.":remove")->setName('private.snippet.remove');
})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));
