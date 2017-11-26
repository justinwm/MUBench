<?php

namespace MuBench\ReviewSite\Controllers;

require_once "SlimTestCase.php";

use DatabaseTestCase;
use MuBench\ReviewSite\Models\Misuse;
use SlimTestCase;

class TagControllerTest extends SlimTestCase
{
    /** @var TagController */
    private $tagController;

    /** @var  Misuse */
    private $misuse;

    function setUp()
    {
        parent::setUp();
        $this->tagController = new TagController($this->container);
        $misuse = new Misuse;
        $misuse->metadata_id = 1;
        $misuse->misuse_muid = '1';
        $misuse->run_id = 1;
        $misuse->detector_muid = 'test-detector';
        $misuse->save();
    }

    function test_save_misuse_tags()
    {
        $this->tagController->addTagToMisuse(1, 'test-dataset');

        $misuseTags = Misuse::find(1)->misuse_tags;

        self::assertEquals('test-dataset', $misuseTags->get(0)->name);
    }

    function test_delete_misuse_tag()
    {
        $this->tagController->deleteTagFromMisuse(1, 2);

        $misuseTags = Misuse::find(1)->misuse_tags;

        self::assertEmpty($misuseTags);
    }

    function test_adding_same_tag_twice()
    {
        $this->tagController->addTagToMisuse(1, 'test-tag');
        $this->tagController->addTagToMisuse(1, 'test-tag');

        $misuseTags = Misuse::find(1)->misuse_tags;

        self::assertEquals(1, count($misuseTags));
    }

    function test_add_unknown_tag()
    {
        $this->tagController->addTagToMisuse(1, 'test-tag');

        $misuseTags = Misuse::find(1)->misuse_tags;

        self::assertEquals('test-tag', $misuseTags->get(0)->name);
    }
}
