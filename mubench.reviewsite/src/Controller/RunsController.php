<?php

namespace MuBench\ReviewSite\Controller;


use Illuminate\Database\Eloquent\Collection;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Run;
use Slim\Http\Request;
use Slim\Http\Response;

class RunsController extends Controller
{
    public function getIndex(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['experiment_id'];
        $detector_id = $args['detector_id'];

        $experiment = Experiment::find($experiment_id);
        $detector = Detector::find($detector_id);
        // TODO Filter only this amount of misuses in exp2
        $ex2_review_size = $request->getQueryParam("ex2_review_size", $this->default_ex2_review_size);

        $runs = $this->getRuns($detector, $experiment, $ex2_review_size);

        return $this->renderer->render($response, 'detector.phtml', [
            'experiment' => $experiment,
            'detector' => $detector,
            'runs' => $runs
        ]);
    }

    public function downloadRuns(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['experiment_id'];
        $detector_id = $args['detector_id'];
        $ex2_review_size = $request->getQueryParam("ex2_review_size", $this->default_ex2_review_size);
        $detector = Detector::find($detector_id);
        $experiment = Experiment::find($experiment_id);

        $runs = $this->getRuns($detector, $experiment, $ex2_review_size);

        return download($response, self::exportRunStatistics($runs), $detector->name . ".csv");
    }

    function getRuns($detector, $experiment, $max_reviews)
    {
        $runs = Run::of($detector)->in($experiment)->get();

        foreach($runs as $run){
            $conclusive_reviews = 0;
            $filtered_misuses = new Collection;
            foreach ($run->misuses as $misuse) {
                if ($conclusive_reviews >= $max_reviews) {
                    break;
                }
                $filtered_misuses->add($misuse);
                if ($misuse->hasConclusiveReviewState() || (!$misuse->hasSufficientReviews() && !$misuse->hasInconclusiveReview())) {
                    $conclusive_reviews++;
                }
            }
            $run->misuses = $filtered_misuses;
        }

        return $runs;
    }

    public static function exportRunStatistics($runs)
    {
        $rows = [];
        foreach ($runs as $run) {
            $run_details = [];
            $run_details["project"] = $run->project_muid;
            $run_details["version"] = $run->version_muid;
            $run_details["result"] = $run->result;
            $run_details["number_of_findings"] = $run->number_of_findings;
            $run_details["runtime"] = $run->runtime;

            foreach ($run->misuses as $misuse) {
                $row = $run_details;

                $row["misuse"] = $misuse->misuse_muid;
                $row["decision"] = $misuse->getReviewState();
                if ($misuse->hasResolutionReview()) {
                    $resolution = $misuse->getResolutionReview();
                    $row["resolution_decision"] = $resolution->getDecision();
                    $row["resolution_comment"] = escapeText($resolution->comment);
                } else {
                    $row["resolution_decision"] = "";
                    $row["resolution_comment"] = "";
                }

                $reviews = $misuse->getReviews();
                $review_index = 0;
                foreach ($reviews as $review) {
                    $review_index++;
                    $row["review{$review_index}_name"] = $review->reviewer->name;
                    $row["review{$review_index}_decision"] = $review->getDecision();
                    $row["review{$review_index}_comment"] = escapeText($review->comment);
                }

                $rows[] = $row;
            }
            if (empty($run['misuses'])) {
                $rows[] = $run_details;
            }
        }
        return createCSV($rows);
    }
}
