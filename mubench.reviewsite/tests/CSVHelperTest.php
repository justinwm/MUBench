<?php

require_once 'SlimTestCase.php';

use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\ExperimentResult;
use MuBench\ReviewSite\Models\DetectorResult;
use \MuBench\ReviewSite\CSVHelper;
use MuBench\ReviewSite\Models\Reviewer;
use MuBench\ReviewSite\Models\Run;

class CSVHelperTest extends SlimTestCase
{

    private $csv_helper;
    private $detector1;
    private $detector2;
    private $no_reviews_misuse;
    private $positive_reviews_misuse;
    private $resolved_review_misuse;


    function setUp()
    {
        parent::setUp();
        $this->detector1 = Detector::firstOrCreate(['name' => '-d1-']);
        $this->detector2 = Detector::firstOrCreate(['name' => '-d2-']);
        $this->run = new \MuBench\ReviewSite\Models\Run;
        $this->run->setDetector(Detector::find(1));
        $this->run->id = 5;
        $this->run->experiment_id = Experiment::find(1)->id;
        $this->run->project_muid = '-p1-';
        $this->run->version_muid = '-v-';
        $this->run->result = 'success';
        $this->run->number_of_findings = 23;
        $this->run->runtime = 42.1;
        $this->run->save();
        $this->no_reviews_misuse =  $this->someMisuseWithOneFindingAndReviewDecisions([]);
        $this->positive_reviews_misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'Yes']);
        $this->resolved_review_misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No'], 'Yes');
    }

    public function test_ex1_stats_as_csv()
    {
        $experiment = Experiment::find(1);
        $run1 = $this->createSomeRun($experiment, $this->detector1, [$this->no_reviews_misuse]);
        $run2 = $this->createSomeRun($experiment, $this->detector2, [$this->positive_reviews_misuse]);
        $stats = [
            new DetectorResult(
                $this->detector1
                , [$run1]),
            new DetectorResult(
                $this->detector2
                , [$run2])];
        $stats["total"] = new ExperimentResult($stats);

        $expected_csv = $this->createCSV(["detector,project,synthetics,misuses,potential_hits,open_reviews,need_clarification,yes_agreements,no_agreements,total_agreements,yes_no_agreements,no_yes_agreements,total_disagreements,kappa_p0,kappa_pe,kappa_score,hits,recall",
                "-d1-,1,0,1,1,2,0,0,0,0,0,0,0,0,0,0,0,0",
                "-d2-,1,0,1,1,0,0,1,0,1,0,0,0,1,0,1,1,1",
            "Total,1,0,2,2,2,0,1,0,1,0,0,0,0.5,0,0.5,1,0.25"]);
        self::assertEquals($expected_csv, CSVHelper::exportStatistics($experiment, $stats));
    }

    public function test_ex2_stats_as_csv()
    {
        $experiment = Experiment::find(2);
        $run1 = $this->createSomeRun($experiment, $this->detector1, [$this->no_reviews_misuse]);
        $run2 = $this->createSomeRun($experiment, $this->detector2, [$this->positive_reviews_misuse]);
        $stats = [
            new DetectorResult(
                $this->detector1
                , [$run1]),
            new DetectorResult(
                $this->detector2
                , [$run2])];
        $stats["total"] = new ExperimentResult($stats);
        $expected_csv = $this->createCSV(["detector,project,potential_hits,open_reviews,need_clarification,yes_agreements,no_agreements,total_agreements,yes_no_agreements,no_yes_agreements,total_disagreements,kappa_p0,kappa_pe,kappa_score,hits,precision",
            "-d1-,1,1,2,0,0,0,0,0,0,0,0,0,0,0,0",
            "-d2-,1,1,0,0,1,0,1,0,0,0,1,0,1,1,1",
            "Total,1,2,2,0,1,0,1,0,0,0,0.5,0,0.5,1,0.5"]);
        self::assertEquals($expected_csv, CSVHelper::exportStatistics($experiment, $stats));
    }

    public function test_ex3_stats_as_csv()
    {
        $experiment = Experiment::find(3);
        $run1 = $this->createSomeRun($experiment, $this->detector1, [$this->no_reviews_misuse]);
        $run2 = $this->createSomeRun($experiment, $this->detector2, [$this->positive_reviews_misuse]);
        $stats = [
            new DetectorResult(
                $this->detector1
                , [$run1]),
            new DetectorResult(
                $this->detector2
                , [$run2])];
        $stats["total"] = new ExperimentResult($stats);
        $expected_csv = $this->createCSV([
            "detector,project,misuses,potential_hits,open_reviews,need_clarification,yes_agreements,no_agreements,total_agreements,yes_no_agreements,no_yes_agreements,total_disagreements,kappa_p0,kappa_pe,kappa_score,hits,recall",
            "-d1-,1,1,1,2,0,0,0,0,0,0,0,0,0,0,0,0",
            "-d2-,1,1,1,0,0,1,0,1,0,0,0,1,0,1,1,1",
            "Total,1,2,2,2,0,1,0,1,0,0,0,0.5,0,0.5,1,0.25"
        ]);

        self::assertEquals($expected_csv, CSVHelper::exportStatistics($experiment, $stats));
    }

    function test_export_detector_run_as_csv()
    {
        $experiment = Experiment::find(1);
        $run = $this->createSomeRun($experiment, $this->detector1, [$this->positive_reviews_misuse, $this->resolved_review_misuse, $this->no_reviews_misuse]);
        $runs = [$run];
        $expected_csv = $this->createCSV([
            "project,version,result,number_of_findings,runtime,misuse,decision,resolution_decision,resolution_comment,review1_name,review1_decision,review1_comment,review2_name,review2_decision,review2_comment",
            "-p-,-v-,success,23,42.1,0,4,,,-reviewer0-,2,\"-comment-\",-reviewer1-,2,\"-comment-\"",
            "-p-,-v-,success,23,42.1,0,6,2,\"-comment-\",-reviewer0-,2,\"-comment-\",-reviewer1-,0,\"-comment-\"",
            "-p-,-v-,success,23,42.1,0,1,,"
        ]);

        self::assertEquals($expected_csv, CSVHelper::exportRunStatistics($runs));
    }

    private function createSomeRun(Experiment $experiment, Detector $detector, $misuses)
    {
        $run = new \MuBench\ReviewSite\Models\Run;
        $run->setDetector($detector);
        $run->experiment_id = $experiment->id;
        $run->project_muid = '-p-';
        $run->version_muid = '-v-';
        $run->result = 'success';
        $run->number_of_findings = 23;
        $run->runtime = 42.1;
        $run->misuses = $misuses;
        return $run;
    }

    private function someMisuseWithOneFindingAndReviewDecisions($decisions, $resolutionDecision = null)
    {
        $misuse = Misuse::create(['misuse_muid' => '0', 'run_id' => 5, 'detector_id' => 1]);
        $finding = new \MuBench\ReviewSite\Models\Finding;
        $finding->setDetector(Detector::find(1));
        $finding->experiment_id = Experiment::find(2);
        $finding->misuse_id = $misuse->id;
        $finding->project_muid = 'mubench';
        $finding->version_muid = '42';
        $finding->misuse_muid = '0';
        $finding->startline = 113;
        $finding->rank = 1;
        $finding->file = 'Test.java';
        $finding->method = "method(A)";
        $finding->save();
        $reviewController = new ReviewController($this->container);
        foreach ($decisions as $index => $decision) {
            $reviewer = Reviewer::firstOrCreate(['name' => '-reviewer' . $index . '-']);
            $reviewController->updateReview($misuse->id, $reviewer->id, '-comment-', [['hit' => $decision]]);
        }

        if ($resolutionDecision) {
            $reviewController->updateReview($misuse->id, Reviewer::where('name', 'resolution')->first()->id, '-comment-', [['hit' => $resolutionDecision]]);
        }

        return $misuse;
    }

    private function createCSV($lines)
    {
        array_unshift($lines, "sep=,");
        return implode("\n", $lines);
    }

}
