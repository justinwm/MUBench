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
$app->get('/experiments/{experiment_id}/detectors/{detector_muid}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('experiment.detector');
$app->get('/experiments/{experiment_id}/detectors/{detector_muid}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('download.runs');
$app->get('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('view');
$app->get('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('review');
$app->get('/results', \MuBench\ReviewSite\Controller\RunsController::class.":getResults")->setName('stats.results');
$app->get('/experiments/{experiment_id}/results.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadResults")->setName('stats.results.csv');
$app->get('/tags', \MuBench\ReviewSite\Controller\TagController::class.":getTags")->setName('stats.tags');
$app->get('/types', \MuBench\ReviewSite\Controller\TypeController::class.":getTypes")->setName('stats.types');


$app->group('/private', function () use ($app) {
    $app->get('/', \MuBench\ReviewSite\Controller\ExperimentsController::class.":index")->setName('private./');
    $app->get('/experiments/{experiment_id}/detectors/{detector_muid}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":getIndex")->setName('private.experiment.detector');
    $app->get('/experiments/{experiment_id}/detectors/{detector_muid}/runs.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadRuns")->setName('private.download.runs');
    $app->get('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('private.view');
    $app->get('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":getReview")->setName('private.review');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/open', \MuBench\ReviewSite\Controller\ReviewController::class.":getTodo")->setName('private.todo');
    $app->get('/experiments/{experiment_id}/reviews/{reviewer_id}/closed', \MuBench\ReviewSite\Controller\ReviewController::class.":getOverview")->setName('private.overview');
    $app->get('/results', \MuBench\ReviewSite\Controller\RunsController::class.":getResults")->setName('private.stats.results');
    $app->get('/experiments/{experiment_id}/results.csv', \MuBench\ReviewSite\Controller\RunsController::class.":downloadResults")->setName('private.stats.results.csv');
    $app->get('/tags', \MuBench\ReviewSite\Controller\TagController::class.":getTags")->setName('private.stats.tags');
    $app->get('/types', \MuBench\ReviewSite\Controller\TypeController::class.":getTypes")->setName('private.stats.types');
})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));

$app->group('', function () use ($app, $settings) {
    $app->post('/metadata', \MuBench\ReviewSite\Controller\MetadataController::class.":putMetadata");
    // /experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}/tags/{tag_id}

    $app->post('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/runs', \MuBench\ReviewSite\Controller\RunsController::class.":postRun");

    $app->group('/experiments/{experiment_id}/detectors/{detector_muid}/projects/{project_muid}/versions/{version_muid}/misuses/{misuse_muid}', function() use ($app) {
        $app->post('/tags/add',\MuBench\ReviewSite\Controller\TagController::class.":postTag")->setName('private.tag.add');
        $app->post('/tags/{tag_id}/delete', \MuBench\ReviewSite\Controller\TagController::class.":deleteTag")->setName('private.tag.remove');

        $app->post('/reviewers/{reviewer_id}', \MuBench\ReviewSite\Controller\ReviewController::class.":postReview")->setName('private.update.review');
        // /snippets/{snippet_id}
        $app->post('/snippets', \MuBench\ReviewSite\Controller\SnippetController::class.":postSnippet")->setName('private.snippet.add');
        $app->post('/snippets/{snippet_id}/delete', \MuBench\ReviewSite\Controller\SnippetController::class.":deleteSnippet")->setName('private.snippet.remove');
    });

})->add(new \MuBench\ReviewSite\Middleware\AuthMiddleware($container));
