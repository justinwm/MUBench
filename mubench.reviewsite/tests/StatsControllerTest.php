<?php

require_once 'SlimTestCase.php';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Controller\RunsController;
use MuBench\ReviewSite\Controller\StatsController;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\ExperimentResult;
use MuBench\ReviewSite\Models\DetectorResult;
use \MuBench\ReviewSite\CSVHelper;
use MuBench\ReviewSite\Models\Reviewer;
use MuBench\ReviewSite\Models\Run;

class StatsControllerTest extends SlimTestCase
{
    private $detector1;
    private $detector2;


    function setUp()
    {
        parent::setUp();
        $this->detector1 = Detector::firstOrCreate(['name' => '-d1-']);
        $this->detector2 = Detector::firstOrCreate(['name' => '-d2-']);
        $this->resolution = Reviewer::create(['name' => 'resolution']);
        //$this->no_reviews_misuse =  $this->someMisuseWithOneFindingAndReviewDecisions([]);
        //$this->positive_reviews_misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'Yes']);
        //$this->resolved_review_misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No'], 'Yes');
    }

    public function test_ex1_stats_as_csv()
    {
        $experiment = Experiment::find(1);
        $run1 = $this->createSomeRun(1, $experiment, $this->detector1, [[[]]]);
        $run2 = $this->createSomeRun(2, $experiment, $this->detector2, [[['Yes', 'Yes']]]);
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
        self::assertEquals($expected_csv, StatsController::exportStatistics($experiment, $stats));
    }

    public function test_ex2_stats_as_csv()
    {
        $experiment = Experiment::find(2);
        $run1 = $this->createSomeRun(1, $experiment, $this->detector1, [[[]]]);
        $run2 = $this->createSomeRun(2, $experiment, $this->detector2, [[['Yes', 'Yes']]]);
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
        self::assertEquals($expected_csv, StatsController::exportStatistics($experiment, $stats));
    }

    public function test_ex3_stats_as_csv()
    {
        $experiment = Experiment::find(3);
        $run1 = $this->createSomeRun(1, $experiment, $this->detector1, [[[]]]);
        $run2 = $this->createSomeRun(2, $experiment, $this->detector2, [[['Yes', 'Yes']]]);
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

        self::assertEquals($expected_csv, StatsController::exportStatistics($experiment, $stats));
    }

    function test_export_detector_run_as_csv()
    {
        $experiment = Experiment::find(1);
        $run = $this->createSomeRun(1, $experiment, $this->detector1, [[['Yes', 'Yes']], [['Yes', 'No'], 'Yes'], [[]]]);
        $runs = [$run];
        $expected_csv = $this->createCSV([
            "project,version,result,number_of_findings,runtime,misuse,decision,resolution_decision,resolution_comment,review1_name,review1_decision,review1_comment,review2_name,review2_decision,review2_comment",
            "-p-,-v-,success,23,42.1,0,4,,,-reviewer0-,2,\"-comment-\",-reviewer1-,2,\"-comment-\"",
            "-p-,-v-,success,23,42.1,1,6,2,\"-comment-\",-reviewer0-,2,\"-comment-\",-reviewer1-,0,\"-comment-\"",
            "-p-,-v-,success,23,42.1,2,1,,"
        ]);

        self::assertEquals($expected_csv, RunsController::exportRunStatistics($runs));
    }

    private function createSomeRun($id, Experiment $experiment, Detector $detector, $misuses)
    {
        $run = new \MuBench\ReviewSite\Models\Run;
        $run->setDetector($detector);
        $run->id = $id;
        $run->experiment_id = $experiment->id;
        $run->project_muid = '-p-';
        $run->version_muid = '-v-';
        $run->result = 'success';
        $run->number_of_findings = 23;
        $run->runtime = 42.1;
        $run->misuses = new \Illuminate\Database\Eloquent\Collection;
        foreach($misuses as $key => $misuse){
            $new_misuse = Misuse::create(['misuse_muid' => $key, 'run_id' => $run->id, 'detector_id' => $detector->id]);
            $this->createFindingWith($experiment, $detector, $new_misuse);
            $this->addReviewsForMisuse($new_misuse, $misuse[0], sizeof($misuse) > 1 ? $misuse[1] : null);
            $new_misuse->findings;
            $new_misuse->save();
            $run->misuses->push($new_misuse);
        }
        return $run;
    }

    private function addReviewsForMisuse($misuse, $decisions, $resolutionDecision = null)
    {
        $reviewController = new ReviewController($this->container);
        foreach ($decisions as $index => $decision) {
            $reviewer = Reviewer::firstOrCreate(['name' => '-reviewer' . $index . '-']);
            $reviewController->updateOrCreateReview($misuse->id, $reviewer->id, '-comment-', [['hit' => $decision, 'types' => []]]);
        }

        if ($resolutionDecision) {
            $resolutionReviewer = Reviewer::where(['name' => 'resolution'])->first();
            $reviewController->updateOrCreateReview($misuse->id, $resolutionReviewer->id, '-comment-', [['hit' => $resolutionDecision, 'types' => []]]);
        }
    }

    private function createCSV($lines)
    {
        array_unshift($lines, "sep=,");
        return implode("\n", $lines);
    }

}
