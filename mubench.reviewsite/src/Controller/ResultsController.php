<?php

namespace MuBench\ReviewSite\Controller;


use Monolog\Logger;
use MuBench\ReviewSite\DBConnection;
use MuBench\ReviewSite\Model\Detector;
use MuBench\ReviewSite\Model\Experiment;
use MuBench\ReviewSite\Model\Misuse;
use MuBench\ReviewSite\Model\Review;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

class ResultsController
{

    /** @var string */
    private $siteBaseUrl;
    /** @var  int */
    private $reviewSize;
    /** @var DBConnection */
    private $db;
    /** @var PhpRenderer */
    private $renderer;

    public function __construct($siteBaseUrl, $reviewSize, DBConnection $db, PhpRenderer $renderer)
    {
        $this->siteBaseUrl = $siteBaseUrl;
        $this->reviewSize = $reviewSize;
        $this->db = $db;
        $this->renderer = $renderer;
    }

    public function get(Request $request, Response $response, array $args)
    {
        $experimentId = $args['exp'];
        $detector = $this->getDetector($args['detector'], $request, $response);
        $reviewSize = $request->getQueryParam("ex2_review_size", $this->reviewSize);

        $runs = $this->getRuns($experimentId, $detector, $reviewSize);
        return $this->render($request, $response, $args, 'detector.phtml', ['ex2_review_size' => $reviewSize, 'runs' => $runs]);
    }

    function getRuns($experimentId, Detector $detector, $reviewSize)
    {
        /** @var array $runs */
        $runs = $this->db->table($detector->getStatsTableName())->where('exp', $experimentId)->orderBy(['project', 'version'])->get();

        foreach ($runs as &$run) {
            $projectId = $run["project"];
            $versionId = $run["version"];

            $misuses = $this->getMisuses($detector, $experimentId, $projectId, $versionId);
            if($experimentId === "ex2"){
                $misuses = $this->reduceMisusesToReviewSize($misuses, $reviewSize);
            }
            $run["misuses"] = $misuses;
        }
        return $runs;
    }

    private function getMisuses(Detector $detector, $experimentId, $projectId, $versionId)
    {
        $metadata = $this->getMetadata($detector, $experimentId, $projectId, $versionId);
        $misuses = [];
        foreach ($metadata as $key => $misuse) {
            $misuseId = $misuse["misuse"];

            $misuse["tags"] = $this->getTags($experimentId, $detector, $projectId, $versionId, $misuseId);
            if ($experimentId === 'ex2') {
                $misuse["violation_types"] = $this->getViolationTypes($projectId, $versionId, $misuseId);
            }

            /** @var array $reviews */
            $reviews = $this->getReviews($experimentId, $detector, $projectId, $versionId, $misuseId);
            /** @var array $potentialHits */
            $potentialHits = $this->getPotentialHits($experimentId, $detector, $projectId, $versionId, $misuseId);

            $misuses[] = new Misuse($misuse, $potentialHits, $reviews);
        }
        return $misuses;
    }

    private function getMetadata(Detector $detector, $experimentId, $projectId, $versionId)
    {
        $misuse_column = 'misuse';
        if ($experimentId === "ex1") {
            $misuse_column = $this->db->getQualifiedName('metadata.misuse');
            $query = $this->db->table('metadata')->select('metadata.*')
                ->innerJoin('patterns', 'metadata.misuse', '=', 'patterns.misuse');
        } elseif ($experimentId === "ex2") {
            $query = $this->db->table($detector->getTableName())->select('misuse')->where('exp', $experimentId);
        } else { // if ($experimentId === "ex3")
            $query = $this->db->table('metadata');
        }
        return $query->where('project', $projectId)->where('version', $versionId)
            ->orderBy($this->db->raw("$misuse_column * 1,"), $misuse_column)->get();
    }

    private function getReviews($experimentId, Detector $detector, $projectId, $versionId, $misuseId)
    {
        $reviews = $this->db->table('reviews')
            ->where('exp', $experimentId)->where('detector', $detector->id)->where('project', $projectId)
            ->where('version', $versionId)->where('misuse', $misuseId)->get();

        foreach ($reviews as $key => $review) {
            $review["finding_reviews"] = $this->getFindingReviews($review["id"]);
            $reviews[$key] = new Review($review);
        }

        return $reviews;
    }

    private function getFindingReviews($reviewId)
    {
        /** @var array $finding_reviews */
        $finding_reviews = $this->db->table('review_findings')->where('review', $reviewId)
            ->orderBy($this->db->raw("`rank` * 1"))->get();

        foreach ($finding_reviews as &$finding_review) {
            $violation_types = $this->db->table('review_findings_types')
                ->innerJoin('types', 'review_findings_types.type', '=', 'types.id')->select('name')
                ->where('review_finding', $finding_review['id'])->get();
            // REFACTOR return ['id', 'name'] and adjust template
            $finding_review["violation_types"] = [];
            foreach ($violation_types as $violation_type) {
                $finding_review["violation_types"][] = $violation_type["name"];
            }
        }
        return $finding_reviews;
    }

    private function getTags($experimentId, Detector $detector, $projectId, $versionId, $misuseId)
    {
        return $this->db->table('misuse_tags')->innerJoin('tags', 'misuse_tags.tag', '=', 'tags.id')
            ->select('id', 'name')->where('exp', $experimentId)->where('detector', $detector->id)
            ->where('project', $projectId)->where('version', $versionId)->where('misuse', $misuseId)->get();
    }

    private function getPotentialHits($experimentId, Detector $detector, $projectId, $versionId, $misuseId)
    {
        if($experimentId === 'ex2'){
            $potentialHits = ['misuse' => $misuseId];
        }else{ /* if($experimentId === 'ex1' || $experimentId === 'ex3') */
            $potentialHits = $this->db->table($detector->getTableName())
                ->where('exp', $experimentId)->where('project', $projectId)
                ->where('version', $versionId)->where('misuse', $misuseId)
                ->orderBy($this->db->raw("`rank` * 1"))->get();
        }
        return $potentialHits;
    }

    private function getViolationTypes($projectId, $versionId, $misuseId)
    {
        $types = $this->db->table('misuse_types')->select('types.name')
            ->innerJoin('types', 'misuse_types.type', '=', 'types.id')->where('project', $projectId)
            ->where('version', $versionId)->where('misuse', $misuseId)->get();
        $type_names = [];
        foreach ($types as $type) {
            $type_names[] = $type['name'];
        }
        return $type_names;
    }


    private function reduceMisusesToReviewSize($misuses, $maxReviews)
    {
        $conclusiveReviews = 0;
        $misuseSubset = [];
        /* @var Misuse $misuse */
        foreach ($misuses as $misuse) {
            if ($conclusiveReviews >= $maxReviews) {
                break;
            }
            $misuseSubset[] = $misuse;
            if ($misuse->hasConclusiveReviewState() || (!$misuse->hasSufficientReviews() && !$misuse->hasInconclusiveReview())) {
                $conclusiveReviews++;
            }
        }
        return $misuseSubset;
    }

    private function render(Request $request, Response $response, array $args, $template, array $params)
    {
        $params["user"] = $this->getUser($request);

        $params["site_base_url"] = htmlspecialchars($this->siteBaseUrl);
        $params["public_url_prefix"] = $params["site_base_url"] . "index.php/";
        $params["private_url_prefix"] = $params["site_base_url"] . "index.php/private/";
        $params["api_url_prefix"] = $params["site_base_url"] . "index.php/api/";
        $params["uploads_url_prefix"] = $params["site_base_url"] . $this->upload_path;
        $params["url_prefix"] = $params["user"] ? $params["private_url_prefix"] : $params["public_url_prefix"];

        $path = $request->getUri()->getPath();
        $params["path"] = htmlspecialchars(strcmp($path, "/") === 0 ? "" : $path);
        $params["origin_param"] = htmlspecialchars("?origin=" . $params["path"]);
        $params["origin_path"] = htmlspecialchars($request->getQueryParam("origin", ""));

        $params["experiment"] = Experiment::get($args["exp"]);
        $params["experiments"] = Experiment::all();
        $params["detector"] = $this->getDetector($args['detector'], $request, $response);
        $params["detectors"] = [];
        foreach ($params["experiments"] as $experiment) { /** @var Experiment $experiment */
            $params["detectors"][$experiment->getId()] = $this->db->getDetectors($experiment->getId());
        }

        return $this->renderer->render($response, $template, $params);
    }

    private function getUser(Request $request)
    {
        $params = $request->getServerParams();
        return array_key_exists('PHP_AUTH_USER', $params) ? $params['PHP_AUTH_USER'] : "";
    }

    private function getDetector($detector_name, $request, $response)
    {
        try{
            return $this->db->getDetector($detector_name);
        }catch (\InvalidArgumentException $e){
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
}