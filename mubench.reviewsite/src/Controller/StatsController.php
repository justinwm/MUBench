<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\DetectorResult;
use MuBench\ReviewSite\Models\ExperimentResult;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\ReviewState;
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
        return download($response, self::exportStatistics($experiment, $stats),
            "stats_" . $experiment->id . ".csv");
    }

    public static function exportStatistics($experiment, $stats)
    {
        $rows = [];
        foreach ($stats as $stat) {
            $row = [];
            $row["detector"] = $stat->getDisplayName();
            $row["project"] = $stat->number_of_projects;

            if ($experiment->id === 1) {
                $row["synthetics"] = $stat->number_of_synthetics;
            }
            if ($experiment->id === 1 || $experiment->id === 3) {
                $row["misuses"] = $stat->number_of_misuses;
            }

            $row["potential_hits"] = $stat->misuses_to_review;
            $row["open_reviews"] = $stat->open_reviews;
            $row["need_clarification"] = $stat->number_of_needs_clarification;
            $row["yes_agreements"] = $stat->yes_agreements;
            $row["no_agreements"] = $stat->no_agreements;
            $row["total_agreements"] = $stat->getNumberOfAgreements();
            $row["yes_no_agreements"] = $stat->yes_no_disagreements;
            $row["no_yes_agreements"] = $stat->no_yes_disagreements;
            $row["total_disagreements"] = $stat->getNumberOfDisagreements();
            $row["kappa_p0"] = $stat->getKappaP0();
            $row["kappa_pe"] = $stat->getKappaPe();
            $row["kappa_score"] = $stat->getKappaScore();
            $row["hits"] = $stat->number_of_hits;

            if ($experiment->id === 2) {
                $row["precision"] = $stat->getPrecision();
            } else {
                $row["recall"] = $stat->getRecall();
            }

            $rows[] = $row;
        }
        return createCSV($rows);
    }

}
