<?php

namespace MuBench\ReviewSite\Controller;

require_once 'SlimTestCase.php';

use MuBench\ReviewSite\Controller\FindingsUploader;
use MuBench\ReviewSite\Controller\ReviewController;
use MuBench\ReviewSite\Controller\ReviewUploader;
use MuBench\ReviewSite\Controller\RunsController;
use MuBench\ReviewSite\Model\Misuse;
use MuBench\ReviewSite\Model\Review;
use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Run;
use SlimTestCase;

class MisuseFilterTest extends SlimTestCase
{
    /** @var ReviewController */
    private $reviewController;
    /** @var RunsController */
    private $runController;
    /** @var Experiment */
    private $experiment;
    /** @var Detector */
    private $detector;

    private $undecided_review = [
        'reviewer_id' => 2,
        'misuse_id' => 3,
        'review_comment' => '-comment-',
        'review_hit' => [
            1 => [
                'hit' => 'No',
                'types' => [
                    'missing/call'
                ]
            ]
        ]
    ];

    private $decided_review = [
        'reviewer_id' => 3,
        'misuse_id' => 3,
        'review_comment' => '-comment-',
        'review_hit' => [
            1 => [
                'hit' => 'Yes',
                'types' => [
                    'missing/call'
                ]
            ]
        ]
    ];

    function setUp()
    {
        parent::setUp();
        $this->reviewController = new ReviewController($this->container);
        $this->runController = new RunsController($this->container);
        $this->detector = Detector::find(1);
        $this->experiment = Experiment::find(2);
    }

    function test_inconclusive_reviews()
    {
        $this->reviewController->updateOrCreateReview($this->decided_review['misuse_id'], $this->decided_review['reviewer_id'], $this->decided_review['review_comment'], $this->decided_review['review_hit']);
        $this->reviewController->updateOrCreateReview($this->undecided_review['misuse_id'], $this->undecided_review['reviewer_id'], $this->undecided_review['review_comment'], $this->undecided_review['review_hit']);
        $runs = $this->runController->getRuns($this->detector, $this->experiment, 2);
        self::assertEquals(3, sizeof($runs[0]->misuses));
    }

    function test_conclusive_reviews()
    {
        $this->reviewController->updateOrCreateReview($this->decided_review['misuse_id'], $this->decided_review['reviewer_id'], $this->decided_review['review_comment'], $this->decided_review['review_hit']);
        $this->reviewController->updateOrCreateReview($this->undecided_review['misuse_id'], $this->undecided_review['reviewer_id'], $this->undecided_review['review_comment'], $this->decided_review['review_hit']);
        $runs = $this->runController->getRuns($this->detector, $this->experiment, 2);
        self::assertEquals(2, sizeof($runs[0]->misuses));
    }

    function test_one_inconclusive_review()
    {
        $this->reviewController->updateOrCreateReview(4, $this->decided_review['reviewer_id'], $this->decided_review['review_comment'], $this->undecided_review['review_hit']);
        $runs = $this->runController->getRuns($this->detector, $this->experiment, 2);
        self::assertEquals(3, sizeof($runs[0]->misuses));
    }

    function test_one_conclusive_review()
    {
        $this->reviewController->updateOrCreateReview($this->decided_review['misuse_id'], $this->decided_review['reviewer_id'], $this->decided_review['review_comment'], $this->decided_review['review_hit']);
        $this->reviewController->updateOrCreateReview($this->undecided_review['misuse_id'], $this->undecided_review['reviewer_id'], $this->undecided_review['review_comment'], $this->decided_review['review_hit']);
        $this->reviewController->updateOrCreateReview(4, $this->decided_review['reviewer_id'], $this->decided_review['review_comment'], $this->decided_review['review_hit']);
        $runs = $this->runController->getRuns($this->detector, $this->experiment, 2);
        self::assertEquals(2, sizeof($runs[0]->misuses));
    }

}
