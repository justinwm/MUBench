<?php

use MuBench\ReviewSite\Controller\SnippetController;
use MuBench\ReviewSite\Models\Snippet;

require_once 'SlimTestCase.php';

class SnippetControllerTest extends SlimTestCase
{

    private $snippetController;


    function setUp()
    {
        parent::setUp();
        $this->snippetController = new SnippetController($this->container);
    }

    function test_snippet_creation()
    {
        $this->snippetController->createSnippet('-p-', '-v-', '-m-', '-code-', 10);
        $actualSnippet = Snippet::where(['project_muid' => '-p-', 'version_muid' => '-v-', 'misuse_muid' => '-m-', 'snippet' => '-code-', 'line' => 10])->first();
        self::assertEquals('-p-', $actualSnippet->project_muid);
        self::assertEquals('-v-', $actualSnippet->version_muid);
        self::assertEquals('-m-', $actualSnippet->misuse_muid);
        self::assertEquals('-code-', $actualSnippet->snippet);
        self::assertEquals(10, $actualSnippet->line);
    }

    function test_snippet_deletion()
    {
        $this->snippetController->createSnippet('-p-', '-v-', '-m-', '-code-', 10);
        $snippet = Snippet::where(['project_muid' => '-p-', 'version_muid' => '-v-', 'misuse_muid' => '-m-', 'snippet' => '-code-', 'line' => 10])->first();
        $this->snippetController->deleteSnippet($snippet->id);
        $actualSnippet = Snippet::where(['project_muid' => '-p-', 'version_muid' => '-v-', 'misuse_muid' => '-m-', 'snippet' => '-code-', 'line' => 10])->first();
        self::assertNull($actualSnippet);
    }
}