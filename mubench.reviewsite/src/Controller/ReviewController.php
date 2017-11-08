<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\FindingReview;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\Review;
use MuBench\ReviewSite\Models\Reviewer;
use MuBench\ReviewSite\Models\Run;
use MuBench\ReviewSite\Models\Tag;
use MuBench\ReviewSite\Models\Type;
use Slim\Http\Request;
use Slim\Http\Response;

class ReviewController extends Controller
{
    public function getReview(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['experiment_id'];
        $detector_id = $args['detector_id'];
        $project_id = $args['project_id'];
        $version_id = $args['version_id'];
        $misuse_id = $args['misuse_id'];

        $experiment = Experiment::find($experiment_id);
        $detector = Detector::find($detector_id);

        $user = $this->getUser($request);
        $reviewer = array_key_exists('reviewer_id', $args) ? Reviewer::find($args['reviewer_id']) : $user;
        $resolution_reviewer = Reviewer::firstOrCreate(['name' => 'resolution']);
        $is_reviewer = ($user && $reviewer && $user->id == $reviewer->id) || ($reviewer && $reviewer->id == $resolution_reviewer->id);

        $misuse = Misuse::find($misuse_id);
        $all_violation_types = Type::all();
        $all_tags = Tag::all();

        $review = $misuse->getReview($reviewer);

        return $this->renderer->render($response, 'review.phtml', ['reviewer' => $reviewer, 'is_reviewer' => $is_reviewer,
            'misuse' => $misuse,'experiment' => $experiment,
            'detector' => $detector, 'review' => $review,
            'violation_types' => $all_violation_types, 'tags' => $all_tags]);
    }

    public function getTodo(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['experiment_id'];
        $reviewer_id = $args['reviewer_id'];

        $experiment = Experiment::find($experiment_id);
        $reviewer = Reviewer::find($reviewer_id);

        $detectors = Detector::withFindings($experiment);

        $open_misuses = [];
        foreach($detectors as $detector){
            $runs = Run::of($detector)->in($experiment)->get();
            foreach($runs as $run){
                foreach($run->misuses as $misuse){
                    /** @var Misuse $misuse */
                    if(!$misuse->hasReviewed($reviewer) && !$misuse->hasSufficientReviews() && $misuse->findings){
                        $open_misuses[$detector->name][] = $misuse;
                    }
                }
            }

        }
        return $this->renderer->render($response, 'todo.phtml', ['open_misuses' => $open_misuses, 'experiment' => $experiment]);
    }

    public function getOverview(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['experiment_id'];
        $reviewer_id = $args['reviewer_id'];

        $experiment = Experiment::find($experiment_id);
        $reviewer = Reviewer::find($reviewer_id);

        $detectors = Detector::withFindings($experiment);

        $closed_misuses = [];
        foreach($detectors as $detector){
            $runs = Run::of($detector)->in($experiment)->get();
            foreach($runs as $run){
                foreach($run->misuses as $misuse){
                    /** @var Misuse $misuse */
                    if($misuse->hasReviewed($reviewer)){
                        $closed_misuses[$detector->name][] = $misuse;
                    }
                }
            }

        }
        return $this->renderer->render($response, 'overview.phtml', ['closed_misuses' => $closed_misuses, 'experiment' => $experiment]);
    }

    public function review(Request $request, Response $response, array $args)
    {
        $review = $request->getParsedBody();
        $experiment_id = $args['experiment_id'];
        $detector_id = $args['detector_id'];
        $project_id = $args['project_id'];
        $version_id = $args['version_id'];
        $misuse_id = $args['misuse_id'];
        $reviewer_id = $args['reviewer_id'];

        $comment = $review['review_comment'];
        $hits = $review['review_hit'];

        $this->updateReview($misuse_id, $reviewer_id, $comment, $hits);

        if ($review["origin"] != "") {
            return $response->withRedirect("{$this->site_base_url}index.php/{$review["origin"]}");
        }else {
            return $response->withRedirect("{$this->site_base_url}index.php/private/experiments/{$experiment_id}/detectors/{$detector_id}/project/{$project_id}/version/{$version_id}/misuse/{$misuse_id}/reviewer/{$reviewer_id}");
        }


    }

    private function getUser(Request $request)
    {
        $params = $request->getServerParams();
        $userName = array_key_exists('PHP_AUTH_USER', $params) ? $params['PHP_AUTH_USER'] : "";
        return Reviewer::firstOrCreate(['name' => $userName]);
    }

    public function updateReview($misuse_id, $reviewer_id, $comment, $hits)
    {
        $review = Review::firstOrNew(['misuse_id' => $misuse_id, 'reviewer_id' => $reviewer_id]);
        $review->comment = $comment;
        $review->save();

        foreach ($hits as $rank => $hit) {
            $findingReview = FindingReview::firstOrNew(['review_id' => $review->id, 'rank' => $rank]);
            $findingReview->decision = $hit['hit'];
            $findingReview->save();
            $this->database->table('finding_review_types')->where('finding_review_id', $findingReview->id)->delete();
            if (array_key_exists("types", $hit)) {
                foreach ($hit['types'] as $type) {
                    $this->database->table('finding_review_types')->insert(['finding_review_id' => $findingReview->id, 'type_id' => $type]);
                }
            }
        }
    }
}
