<?php

namespace MuBench\ReviewSite\Controller;

require_once 'SlimTestCase.php';

use MuBench\ReviewSite\Models\Decision;
use MuBench\ReviewSite\Models\Review;
use MuBench\ReviewSite\Models\Misuse;
use MuBench\ReviewSite\Models\Reviewer;
use SlimTestCase;

class ReviewControllerTest extends SlimTestCase
{
    /**
     * @var ReviewController $reviewController
     */
    private $reviewController;

    function setUp()
    {
        parent::setUp();
        $this->reviewController = new ReviewController($this->container);
    }


    function test_store_review()
    {
        $this->reviewController->updateOrCreateReview(1, 1, '-comment-', [['hit' => 'Yes', 'types' => []]]);

        $review = Misuse::find(1)->getReview(Reviewer::find(1));
        self::assertEquals('-comment-', $review->comment);
        self::assertEquals(Decision::YES, $review->getDecision());
    }

    function test_update_review()
    {
        $this->reviewController->updateOrCreateReview(1, 1, '-comment-', [['hit' => 'Yes', 'types' => []]]);
        $this->reviewController->updateOrCreateReview(1, 1, '-comment-', [['hit' => 'No', 'types' => []]]);

        $review = Misuse::find(1)->getReview(Reviewer::find(1));
        self::assertEquals('-comment-', $review->comment);
        self::assertEquals(Decision::NO, $review->getDecision());
    }

    function test_stores_violation_types()
    {
        $this->reviewController->updateOrCreateReview(1, 1, '-comment-', [['hit' => 'No', 'types' => [1]]]);

        $review = Misuse::find(1)->getReview(Reviewer::find(1));
        $violation_types = $review->getHitViolationTypes('0');
        self::assertEquals("missing/call", $violation_types[0]["name"]);
    }

}
