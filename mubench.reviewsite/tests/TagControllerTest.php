<?php

namespace MuBench\ReviewSite\Controller;

use DatabaseTestCase;
use MuBench\ReviewSite\Model\Detector;
use MuBench\ReviewSite\Models\Misuse;
use SlimTestCase;

class TagControllerTest extends SlimTestCase
{
    /** @var TagController */
    private $tagController;

    function setUp()
    {
        parent::setUp();
        $this->tagController = new TagController($this->container);
    }

    function test_save_misuse_tags()
    {
        $this->tagController->tagMisuse('test-dataset', 2);

        $misuseTags = Misuse::find(2)->misuse_tags();
        self::assertEquals('test-dataset', $misuseTags[0]->name);
    }

    function test_delete_misuse_tag()
    {
        $this->tagController->removeTag(2,1);

        $misuseTags = Misuse::find(1)->misuse_tags();
        self::assertEquals([], $misuseTags);
    }

    function test_adding_same_tag_twice()
    {
        $this->tagController->tagMisuse('test-tag', 2);
        $this->tagController->tagMisuse('test-tag', 2);

        $misuseTags = Misuse::find(1)->misuse_tags();
        self::assertEquals(1, count($misuseTags));
    }

    function test_add_unknown_tag()
    {
        $this->tagController->tagMisuse('test-tag', 2);

        $misuseTags = Misuse::find(2)->misuse_tags();
        self::assertEquals('test-tag', $misuseTags[0]->name);
    }
}
