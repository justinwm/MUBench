<?php
/** @var \Slim\App $app */

use MuBench\ReviewSite\Controller\DownloadController;
use MuBench\ReviewSite\Controller\FindingsController;
use MuBench\ReviewSite\Controller\FindingsUploader;
use MuBench\ReviewSite\Controller\MetadataController;
use MuBench\ReviewSite\Controller\MisuseTagsController;
use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Controller\SnippetUploader;
use MuBench\ReviewSite\DirectoryHelper;
use MuBench\ReviewSite\RoutesHelper;
use Slim\Http\Request;
use Slim\Http\Response;

$logger = $app->getContainer()['logger'];
$database = $app->getContainer()['database'];
$renderer = $app->getContainer()['renderer'];

$app->get('/', \MuBench\ReviewSite\Controller\ExperimentsController::class.":index")->setName('/');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('experiment.detector');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('download.runs');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('view');
$app->get('/experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('review');
$app->get('/results', \MuBench\ReviewSite\Controller\StatsController::class.":getResults")->setName('stats.results');
$app->get('/experiments/{experiment_id}/results.csv', \MuBench\ReviewSite\Controller\StatsController::class.":downloadResults")->setName('stats.results.csv');
$app->get('/tags', \MuBench\ReviewSite\Controller\StatsController::class.":getTags")->setName('stats.tags');
$app->get('/types', \MuBench\ReviewSite\Controller\StatsController::class.":getTypes")->setName('stats.types');


$app->group('/private', function () use ($app) {
    $app->get('/', \MuBench\ReviewSite\Controller\ExperimentsController::class.":index")->setName('private./');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('private.experiment.detector');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('private.download.runs');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('private.view');
    $app->get('/experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('private.review');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/open', \MuBench\ReviewSite\Controller\ReviewController::class.":getTodo")->setName('private.todo');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/closed', \MuBench\ReviewSite\Controller\ReviewController::class.":getOverview")->setName('private.overview');
    $app->get('/results', \MuBench\ReviewSite\Controller\StatsController::class.":getResults")->setName('private.stats.results');
    $app->get('/experiments/{experiment_id}/results.csv', \MuBench\ReviewSite\Controller\StatsController::class.":downloadResults")->setName('private.stats.results.csv');
    $app->get('/tags', \MuBench\ReviewSite\Controller\StatsController::class.":getTags")->setName('private.stats.tags');
    $app->get('/types', \MuBench\ReviewSite\Controller\StatsController::class.":getTypes")->setName('private.stats.types');
})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));

$app->group('', function () use ($app, $settings) {
    $app->post('/metadata', \MuBench\ReviewSite\Controller\MetadataController::class.":putMetadata");
    // /experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}/tags/{tag_id}
    $app->post('/tags',\MuBench\ReviewSite\Controller\TagController::class.":postTag")->setName('private.tag.add');
    // /experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}/tags/{tag_id}/delete
    $app->post('/delete/tag', \MuBench\ReviewSite\Controller\TagController::class.":deleteTag")->setName('private.tag.remove');

    // /experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/runs
    $app->post('/experiments/[{experiment_id}]',
        function (Request $request, Response $response, array $args) use ($settings) {
            $experimentId = $args['experiment_id'];
            $run = decodeJsonBody($request);
            if (!$run) {
                return error_response($response,400, "empty: " . print_r($_POST, true));
            }
            $detector = $run->{'detector'};
            if (!$detector) {
                return error_response($response,400, "no detector: " . print_r($run, true));
            }
            $project = $run->{'project'};
            if (!$project) {
                return error_response($response,400, "no project: " . print_r($run, true));
            }
            $version = $run->{'version'};
            if (!$version) {
                return error_response($response,400, "no version: " . print_r($run, true));
            }
            $hits = $run->{'potential_hits'};
            $this->logger->info("received data for '" . $experimentId . "', '" . $project . "." . $version . "' with " . count($hits) . " potential hits.");

            $uploader = new FindingsController($this->container);
            $uploader->processData($experimentId, $run);
            $files = $request->getUploadedFiles();
            $this->logger->info("received " . count($files) . " files");
            if ($files) {
                $directoryHelper = new DirectoryHelper($settings['upload'], $this->logger);
                foreach ($files as $img) {
                    $directoryHelper->handleImage($experimentId, $detector, $project, $version, $img);
                }
            }
            return $response->withStatus(200);
        });

    $app->group('/experiments/{experiment_id}/detectors/{detector_id}/projects/{project_id}/versions/{version_id}/misuses/{misuse_id}', function() use ($app) {
        $app->post('/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":postReview")->setName('private.update.review');
        // /snippets/{snippet_id}
        $app->post('/snippets', \MuBench\ReviewSite\Controller\SnippetController::class.":add")->setName('private.snippet.add');
        // /snippets/{snippet_id}/delete
        $app->post('/snippets/{snippet_id}', \MuBench\ReviewSite\Controller\SnippetController::class.":remove")->setName('private.snippet.remove');
    });

})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));
