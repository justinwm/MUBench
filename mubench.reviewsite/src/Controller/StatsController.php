<?php

namespace MuBench\ReviewSite\Controller;


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
        $ex2_review_size = $request->getQueryParam("ex2_review_size", $this->settings["default_ex2_review_size"]);
        $experiments = Experiment::all();
        $results = array();
        foreach($experiments as $experiment){
            $detectors = Detector::all();
            $results[$experiment->id] = array();
            foreach($detectors as $detector){
                $runs = Run::of($detector)->in($experiment)->get();
                if ($experiment->id === 2) {
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
                $results[$experiment->id][$detector->id] = new DetectorResult($detector, $runs);
            }
            $results[$experiment->id]["total"] = new ExperimentResult($results[$experiment->id]);
        }

        return $this->renderer->render($response, 'result_stats.phtml', ['results' => $results, 'ex2_review_size' => $ex2_review_size]);
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
}