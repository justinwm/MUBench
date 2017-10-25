<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\CSVHelper;
use MuBench\ReviewSite\Model\DetectorResult;
use MuBench\ReviewSite\Model\ExperimentResult;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Model\ReviewState;
use MuBench\ReviewSite\Models\Run;
use MuBench\ReviewSite\Models\Tag;
use MuBench\ReviewSite\Models\Type;
use Slim\Http\Request;
use Slim\Http\Response;

class StatsController extends Controller
{


    public function getResults(Request $request, Response $response, array $args)
    {
        $ex2_review_size = $request->getQueryParam("ex2_review_size", $this->default_ex2_review_size);
        $experiments = Experiment::all();
        $detectors = Detector::all();

        $results = array();
        foreach($experiments as $experiment){
            $results[$experiment->id] = $this->getResultsForExperiment($experiment, $detectors, $ex2_review_size);
        }
        return $this->renderer->render($response, 'result_stats.phtml', ['results' => $results, 'ex2_review_size' => $ex2_review_size]);
    }

    function getResultsForExperiment($experiment, $detectors, $ex2_review_size)
    {
        $results = array();
        foreach($detectors as $detector){
            $runs = Run::of($detector)->in($experiment)->get();
            if ($experiment->id === 2 && $ex2_review_size > -1) {
                foreach ($runs as &$run) {
                    $misuses = array();
                    $number_of_misuses = 0;
                    foreach ($run->misuses as $misuse) {
                        if ($misuse->getReviewState() != ReviewState::UNRESOLVED) {
                            $misuses[] = $misuse;
                            $number_of_misuses++;
                        }

                        if ($number_of_misuses == $ex2_review_size) {
                            break;
                        }
                    }
                    $run->misuses = $misuses;
                }
            }
            $results[$detector->id] = new DetectorResult($detector, $runs);
        }
        $results["total"] = new ExperimentResult($results);
        return $results;
    }

    public function getTags(Request $request, Response $response, array $args)
    {
        $tags = Tag::all();
        $results = array(1 => array(), 2 => array(), 3 => array());
        $totals = array(1 => array(), 2 => array(), 3 => array());
        foreach($tags as $tag){
            $tagged_misuses = $tag->misuses;
            foreach($tagged_misuses as $misuse){
                $results[$misuse->run->experiment_id][$misuse->detector->name][$tag->name][] = $misuse;
                $totals[$misuse->run->experiment_id][$tag->name][] = $misuse;
            }
        }
        foreach($totals as $key => $total){
            $results[$key]["total"] = $total;
        }
        return $this->renderer->render($response, 'tag_stats.phtml',
            ['results' => $results, 'tags' => $tags]);
    }

    public function getTypes(Request $request, Response $response, array $args){
        $results = array();
        foreach(Type::all() as $type){
            $results[$type->name] = count($type->metadata);
        }
        return $this->renderer->render($response, 'type_stats.phtml', ['results' => $results]);
    }

    public function downloadResults(Request $request, Response $response, array $args)
    {
        $ex2_review_size = $request->getQueryParam("ex2_review_size", $this->default_ex2_review_size);
        $experiment_id = $args['experiment_id'];
        $experiment = Experiment::find($experiment_id);
        $detectors = Detector::all();

        $stats = $this->getResultsForExperiment($experiment, $detectors, $ex2_review_size);
        return download($response,   CSVHelper::exportStatistics($experiment, $stats),
            "stats_" . $experiment->id . ".csv");
    }

}
