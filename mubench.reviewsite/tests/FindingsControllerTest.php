<?php

require_once "SlimTestCase.php";

use MuBench\ReviewSite\Controller\FindingsController;
use MuBench\ReviewSite\Controller\FindingsUploader;
use MuBench\ReviewSite\Controller\MetadataController;
use MuBench\ReviewSite\Controller\SnippetUploader;
use MuBench\ReviewSite\Controller\MisuseTagsController;
use MuBench\ReviewSite\Model\Misuse;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Run;

class FindingsControllerTest extends SlimTestCase
{
    private $request_body;

    private $findingController;

    function setUp()
    {
        parent::setUp();

        $this->request_body = json_decode(json_encode([
            "detector" => "-d-",
            "project" => "-p-",
            "version" => "-v-",
            "misuse" => "-m-",
            "result" => "success",
            "runtime" => 42.1,
            "number_of_findings" => 23,
            "-custom-stat-" => "-stat-val-",
            "potential_hits" => [
                [
                    "misuse" => "-m-",
                    "rank" => 0,
                    "target_snippets" => [
                        ["first_line_number" => 5, "code" => "-code-"]
                    ],
                    "custom1" => "-val1-",
                    "custom2" => "-val2-"
                ]]
        ]));
        $this->findingController = new FindingsController($this->container);
    }

    function test_store_ex1()
    {
        $this->findingController->processData(1, $this->request_body);
        $detector = Detector::where('name', '=', '-d-')->first();
        $run = Run::of($detector)->in(Experiment::find(1))->where(['project_muid' => '-p-', 'version_muid' => '-v-'])->first();

        self::assertEquals('success', $run->result);
        self::assertEquals(42.1, $run->runtime);
        self::assertEquals(23, $run->number_of_findings);
        self::assertEquals('-stat-val-', $run["-custom-stat-"]);
        self::assertEquals(1, sizeof($run->misuses));
        self::assertEquals(null, sizeof($run->misuses[0]->metadata_id));
    }

    function test_store_ex2()
    {
        $this->findingController->processData(2, $this->request_body);
        $detector = Detector::where('name', '=', '-d-')->first();
        $run = Run::of($detector)->in(Experiment::find(2))->where(['project_muid' => '-p-', 'version_muid' => '-v-'])->first();

        self::assertEquals(1, sizeof($run->misuses));
        self::assertEquals(1, sizeof($run->misuses[0]->findings));
        self::assertEquals(1, sizeof($run->misuses[0]->snippets()));

        $misuse = $run->misuses[0];
        $finding = $misuse->findings[0];
        $snippet = $misuse->snippets()[0];

        self::assertEquals('success', $run->result);
        self::assertEquals(42.1, $run->runtime);
        self::assertEquals(23, $run->number_of_findings);
        self::assertEquals('-stat-val-', $run["-custom-stat-"]);
        self::assertEquals($detector->id, $misuse->detector_id);
        self::assertEquals('-m-', $misuse->misuse_muid);
        self::assertEquals(null, $misuse->metadata_id);
        self::assertEquals($run->id, $misuse->run_id);
        self::assertEquals('-p-', $finding->project_muid);
        self::assertEquals('-v-', $finding->version_muid);
        self::assertEquals('-m-', $finding->misuse_muid);
        self::assertEquals('0', $finding->rank);
        self::assertEquals('-val1-', $finding['custom1']);
        self::assertEquals('-val2-', $finding['custom2']);
        self::assertEquals('-code-', $snippet->snippet);
        self::assertEquals('5', $snippet->line);
    }

    function test_store_ex3()
    {
        $this->findingController->processData(3, $this->request_body);
        $detector = Detector::where('name', '=', '-d-')->first();
        $run = Run::of($detector)->in(Experiment::find(3))->where(['project_muid' => '-p-', 'version_muid' => '-v-'])->first();

        self::assertEquals('success', $run->result);
        self::assertEquals(42.1, $run->runtime);
        self::assertEquals(23, $run->number_of_findings);
        self::assertEquals('-stat-val-', $run["-custom-stat-"]);
    }

    function test_get_misuse_ex1(){
        $metadataController = new MetadataController($this->container);
        // SMELL currently, this test depends on a pattern in the metadata, because otherwise the metadata is not
        // found for ex1. This should not be necessary anymore, once the findings controller is separated.
        $metadataController->updateMetadata('-p-', '-v-', '-m-', '-desc-',
            ['diff-url' => '-diff-', 'description' => '-fix-desc-'],
            ['file' => '-file-location-', 'method' => '-method-location-'], [],
            [['id' => '-p1-', 'snippet' => ['code' => '-code-', 'first_line' => 42]]], []);

        $this->findingController->processData(1, json_decode(<<<EOT
    {
        "detector": "-d-",
        "project": "-p-",
        "version": "-v-",
        "misuse": "-m-",
        "result": "success",
        "runtime": 42.1,
        "number_of_findings": 23,
        "potential_hits": [
            {
                "misuse": "-m-",
                "rank": 0,
                "target_snippets": [
                    {"first_line_number": 5, "code": "-code-"}
                ],
                "custom1": "-val1-",
                "custom2": "-val2-"
            }
        ]
    }
EOT
        ));

        $detector = Detector::where('name', '=', '-d-')->first();
        $run = Run::of($detector)->in(Experiment::find(1))->where(['project_muid' => '-p-', 'version_muid' => '-v-'])->first();

        self::assertNotNull($run->misuses[0]->metadata_id);
    }
}
