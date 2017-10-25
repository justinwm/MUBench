<?php

namespace MuBench\ReviewSite\Controller;


use Illuminate\Database\Eloquent\Collection;
use MuBench\ReviewSite\CSVHelper;
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
        return download($response, CSVHelper::exportRunStatistics($runs), $detector->name . ".csv");
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
}
