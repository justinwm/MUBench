<?php

require_once 'SlimTestCase.php';

use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\Reviewer;
use MuBench\ReviewSite\Models\ReviewState;

class ReviewStateTest extends SlimTestCase
{
    function test_no_potential_hits()
    {
        $misuse = Misuse::create(['misuse_muid' => "test", 'run_id' => 1, 'detector_id' => 1]);

        self::assertEquals(ReviewState::NOTHING_TO_REVIEW, $misuse->getReviewState());
    }

    function test_needs_2_reviews()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions([/* none */]);

        self::assertEquals(ReviewState::NEEDS_REVIEW, $misuse->getReviewState());
    }

    function test_needs_1_review()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes']);

        self::assertEquals(ReviewState::NEEDS_REVIEW, $misuse->getReviewState());
    }

    function test_needs_review_overrules_needs_carification()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['?']);

        self::assertEquals(ReviewState::NEEDS_REVIEW, $misuse->getReviewState());
    }

    function test_agreement_yes()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'Yes']);

        self::assertEquals(ReviewState::AGREEMENT_YES, $misuse->getReviewState());
    }

    function test_agreement_no()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['No', 'No']);

        self::assertEquals(ReviewState::AGREEMENT_NO, $misuse->getReviewState());
    }

    function test_disagreement()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No']);

        self::assertEquals(ReviewState::DISAGREEMENT, $misuse->getReviewState());
    }

    function test_needs_clarification()
    {
        // NEEDS_REVIEW takes precedence over NEEDS_CLARIFICATION, hence, we need at least two reviews for this state.
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', '?']);

        self::assertEquals(ReviewState::NEEDS_CLARIFICATION, $misuse->getReviewState());
    }

    function test_resolution_yes()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No'], 'Yes');

        self::assertEquals(ReviewState::RESOLVED_YES, $misuse->getReviewState());
    }

    function test_resolution_no()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No'], 'No');

        self::assertEquals(ReviewState::RESOLVED_NO, $misuse->getReviewState());
    }

    function test_resolution_unresolved()
    {
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['Yes', 'No'], '?');

        self::assertEquals(ReviewState::UNRESOLVED, $misuse->getReviewState());
    }

    function test_resolution_is_absolute()
    {
        // Resolution determines the result, even if there are too few reviews and requests for clarification.
        $misuse = $this->someMisuseWithOneFindingAndReviewDecisions(['?', 'Yes'], 'No');

        self::assertEquals(ReviewState::RESOLVED_NO, $misuse->getReviewState());
    }

    private function someMisuseWithOneFindingAndReviewDecisions($decisions, $resolutionDecision = null)
    {
        $misuse = Misuse::create(['misuse_muid' => "test", 'run_id' => 1, 'detector_id' => 1]);
        $findingRank = '0';
        $finding = new \MuBench\ReviewSite\Models\Finding;
        $finding->setDetector(Detector::find(1));
        $finding->experiment_id = Experiment::find(2);
        $finding->misuse_id = $misuse->id;
        $finding->project_muid = 'mubench';
        $finding->version_muid = '42';
        $finding->misuse_muid = 'test';
        $finding->startline = 113;
        $finding->rank = 1;
        $finding->file = 'Test.java';
        $finding->method = "method(A)";
        $finding->save();
        $reviews = [];
        $reviewController = new ReviewController($this->container);
        foreach ($decisions as $index => $decision) {
            $reviewer = Reviewer::firstOrCreate(['name' => 'reviewer' . $index]);
            $reviewController->updateReview($misuse->id, $reviewer->id, '', [['hit' => $decision]]);
        }

        if ($resolutionDecision) {
            $reviewController->updateReview($misuse->id, Reviewer::where('name', 'resolution')->first()->id, '', [['hit' => $resolutionDecision]]);
        }

        return $misuse;
    }
}
