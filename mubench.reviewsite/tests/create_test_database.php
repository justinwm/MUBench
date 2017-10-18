<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



echo 'Creating experiments<br/>';
$experiment = new \MuBench\ReviewSite\Models\Experiment;
Schema::dropIfExists($experiment->getTable());
Schema::create($experiment->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->string('name', 100);
});
$experiment->id = 1;
$experiment->name = "Provided Patterns";
$experiment->save();
$experiment2 = new \MuBench\ReviewSite\Models\Experiment;
$experiment2->id = 2;
$experiment2->name = "All Findings";
$experiment2->save();
$experiment3 = new \MuBench\ReviewSite\Models\Experiment;
$experiment3->id = 3;
$experiment3->name = "Benchmark";
$experiment3->save();

echo 'Creating detectors table<br/>';
Schema::dropIfExists('detectors');
Schema::create('detectors', function(Blueprint $table) {
    $table->increments('id');
    $table->string('name', 100);
});

echo 'Creating test detector<br/>';
$detector = new \MuBench\ReviewSite\Models\Detector;
$detector->name = "TestDetector";
$detector->save();

echo 'Creating runs table<br/>';
$run = new \MuBench\ReviewSite\Models\Run;
$run->setDetector($detector);
Schema::dropIfExists($run->getTable());
Schema::create($run->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->integer('experiment_id');
    $table->string('project_muid', 30);
    $table->string('version_muid', 30);
    $table->float('runtime');
    $table->integer('number_of_findings');
    $table->string('result', 30);
    $table->text('additional_stat')->nullable();
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$run->id = 1;
$run->experiment_id = $experiment->id;
$run->project_muid = 'mubench';
$run->version_muid = '42';
$run->result = 'success';
$run->number_of_findings = 2;
$run->runtime = 3.40;
$run->save();
$run->id = 2;
$run = new \MuBench\ReviewSite\Models\Run;
$run->setDetector($detector);
$run->experiment_id = $experiment->id;
$run->project_muid = 'mubench_2';
$run->version_muid = '43';
$run->result = 'success';
$run->number_of_findings = 0;
$run->runtime = 3.40;
$run->additional_stat = 'test';
$run->save();
$run = new \MuBench\ReviewSite\Models\Run;
$run->setDetector($detector);
$run->id = 3;
$run->experiment_id = $experiment2->id;
$run->project_muid = 'mubench';
$run->version_muid = '42';
$run->result = 'success';
$run->number_of_findings = 2;
$run->runtime = 3.40;
$run->save();
$run = new \MuBench\ReviewSite\Models\Run;
$run->setDetector($detector);
$run->id = 4;
$run->experiment_id = $experiment2->id;
$run->project_muid = 'mubench_2';
$run->version_muid = '43';
$run->result = 'success';
$run->number_of_findings = 0;
$run->runtime = 3.40;
$run->additional_stat = 'test';
$run->save();

echo 'Creating findings<br/>';
$finding = new \MuBench\ReviewSite\Models\Finding;
$finding->setDetector($detector);
Schema::dropIfExists($finding->getTable());
Schema::create($finding->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->integer('experiment_id');
    $table->integer('misuse_id');
    $table->string('project_muid', 30);
    $table->string('version_muid', 30);
    $table->string('misuse_muid', 30);
    $table->integer('startline');
    $table->integer('rank');
    $table->integer('additional_column');
    $table->text('file');
    $table->text('method');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$finding->experiment_id = $experiment->id;
$finding->misuse_id = 1;
$finding->project_muid = 'mubench';
$finding->version_muid = '42';
$finding->misuse_muid = '1';
$finding->startline = 113;
$finding->rank = 1;
$finding->additional_column = 'test_column';
$finding->file = 'Test.java';
$finding->method = "method(A)";
$finding->save();
$finding = new \MuBench\ReviewSite\Models\Finding;
$finding->setDetector($detector);
$finding->experiment_id = $experiment->id;
$finding->misuse_id = 2;
$finding->project_muid = 'mubench_2';
$finding->version_muid = '43';
$finding->misuse_muid = '1';
$finding->startline = 113;
$finding->rank = 1;
$finding->additional_column = 'test_column';
$finding->file = 'Test.java';
$finding->method = "method(A)";
$finding->save();
$finding = new \MuBench\ReviewSite\Models\Finding;
$finding->setDetector($detector);
$finding->experiment_id = $experiment2->id;
$finding->misuse_id = 3;
$finding->project_muid = 'mubench';
$finding->version_muid = '42';
$finding->misuse_muid = '1';
$finding->startline = 113;
$finding->rank = 1;
$finding->additional_column = 'test_column';
$finding->file = 'Test.java';
$finding->method = "method(A)";
$finding->save();

echo 'Creating finding snippet<br/>';
$snippet = new \MuBench\ReviewSite\Models\Snippet;
Schema::dropIfExists($snippet->getTable());
Schema::create($snippet->getTable(), function( Blueprint $table){
   $table->increments('id');
   $table->integer('misuse_id');
   $table->integer('line');
   $table->text('snippet');
});
$snippet->misuse_id = 1;
$snippet->line = 112;
$snippet->snippet = "test snippet\ntest";
$snippet->save();


echo 'Creating misuses (metadata)<br/>';
$metadata = new \MuBench\ReviewSite\Models\Metadata;
Schema::dropIfExists($metadata->getTable());
Schema::create($metadata->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->string('project_muid', 30);
    $table->string('version_muid', 30);
    $table->string('misuse_muid', 30);
    $table->text('description');
    $table->text('fix_description');
    $table->text('file');
    $table->text('method');
    $table->text('diff_url');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');

    $table->unique(['project_muid', 'version_muid', 'misuse_muid']);
});
$metadata->project_muid = 'mubench';
$metadata->version_muid = '42';
$metadata->misuse_muid = '1';
$metadata->description = 'desc';
$metadata->fix_description = 'fix-desc';
$metadata->file = '/some/file.ext';
$metadata->method = 'method(P)';
$metadata->diff_url = 'http://diff/url';
$metadata->save();
$metadata = new \MuBench\ReviewSite\Models\Metadata;
$metadata->project_muid = 'mubench';
$metadata->version_muid = '42';
$metadata->misuse_muid = '2';
$metadata->description = 'desc';
$metadata->fix_description = 'fix-desc';
$metadata->file = '/some/file.ext';
$metadata->method = 'method(P)';
$metadata->diff_url = 'http://diff/url';
$metadata->save();
$metadata = new \MuBench\ReviewSite\Models\Metadata;
$metadata->project_muid = 'mubench_2';
$metadata->version_muid = '43';
$metadata->misuse_muid = '1';
$metadata->description = 'desc';
$metadata->fix_description = 'fix-desc';
$metadata->file = '/some/file.ext';
$metadata->method = 'method(P)';
$metadata->diff_url = 'http://diff/url';
$metadata->save();

echo 'Creating pattern<br/>';
$pattern = new \MuBench\ReviewSite\Models\Pattern;
Schema::dropIfExists($pattern->getTable());
Schema::create($pattern->getTable(), function(Blueprint $table){
   $table->increments('id');
   $table->integer('metadata_id');
   $table->text('code');
});
$pattern->metadata_id = 1;
$pattern->code = "m(A){\n\ta(A);\n}";
$pattern->save();
$pattern = new \MuBench\ReviewSite\Models\Pattern;
$pattern->metadata_id = 2;
$pattern->code = "m(A){\n\ta(A);\n}";
$pattern->save();

echo 'Creating misuses<br/>';
$misuse = new \MuBench\ReviewSite\Models\Misuse;
Schema::dropIfExists($misuse->getTable());
Schema::create($misuse->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->integer('metadata_id')->nullable();
    $table->integer('detector_id');
    $table->integer('run_id');
    $table->string('misuse_muid', 30);
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});

$misuse->metadata_id = 1;
$misuse->misuse_muid = '1';
$misuse->run_id = 1;
$misuse->detector_id = 1;
$misuse->save();
$misuse = new \MuBench\ReviewSite\Models\Misuse;
$misuse->metadata_id = 2;
$misuse->misuse_muid = '1';
$misuse->detector_id = 1;
$misuse->run_id = 2;
$misuse->save();
$misuse = new \MuBench\ReviewSite\Models\Misuse;
$misuse->misuse_muid = '1';
$misuse->detector_id = 1;
$misuse->run_id = 3;
$misuse->save();

echo 'Creating review<br/>';
$review = new \MuBench\ReviewSite\Models\Review;
Schema::dropIfExists($review->getTable());
Schema::create($review->getTable(), function(Blueprint $table){
    $table->increments('id');
    $table->integer('misuse_id');
    $table->integer('reviewer_id');
    $table->text('comment');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$review->misuse_id = 1;
$review->reviewer_id = 3;
$review->comment = "This is a test comment 2!";
$review->save();
$review = new \MuBench\ReviewSite\Models\Review;
$review->misuse_id = 1;
$review->reviewer_id = 2;
$review->comment = "This is a test comment!";
$review->save();
$review = new \MuBench\ReviewSite\Models\Review;
$review->misuse_id = 3;
$review->reviewer_id = 2;
$review->comment = "This is a test comment!";
$review->save();
$review = new \MuBench\ReviewSite\Models\Review;
$review->misuse_id = 3;
$review->reviewer_id = 3;
$review->comment = "This is a test comment 2!";
$review->save();
$review = new \MuBench\ReviewSite\Models\Review;
$review->misuse_id = 3;
$review->reviewer_id = 1;
$review->comment = "resolution comment!";
$review->save();

echo 'Creating reviewer<br/>';
$reviewer = new \MuBench\ReviewSite\Models\Reviewer;
Schema::dropIfExists($reviewer->getTable());
Schema::create($reviewer->getTable(), function(Blueprint $table){
    $table->increments('id');
    $table->text('name');
});
$reviewer->id = 1;
$reviewer->name = 'resolution';
$reviewer->save();
$reviewer = new \MuBench\ReviewSite\Models\Reviewer;
$reviewer->id = 2;
$reviewer->name = 'reviewer';
$reviewer->save();
$reviewer = new \MuBench\ReviewSite\Models\Reviewer;
$reviewer->id = 3;
$reviewer->name = 'reviewer2';
$reviewer->save();
$reviewer = new \MuBench\ReviewSite\Models\Reviewer;
$reviewer->id = 4;
$reviewer->name = 'admin';
$reviewer->save();


echo 'Creating finding reviews<br/>';
$findingReview = new \MuBench\ReviewSite\Models\FindingReview;
Schema::dropIfExists($findingReview->getTable());
Schema::create($findingReview->getTable(), function(Blueprint $table){
    $table->increments('id');
    $table->integer('review_id');
    $table->text('decision');
    $table->text('rank');
});
$findingReview->decision = 'Yes';
$findingReview->review_id = 1;
$findingReview->rank = '1';
$findingReview->save();
$findingReview = new \MuBench\ReviewSite\Models\FindingReview;
$findingReview->decision = 'Yes';
$findingReview->review_id = 2;
$findingReview->rank = '1';
$findingReview->save();
$findingReview = new \MuBench\ReviewSite\Models\FindingReview;
$findingReview->decision = 'Yes';
$findingReview->review_id = 3;
$findingReview->rank = '1';
$findingReview->save();
$findingReview = new \MuBench\ReviewSite\Models\FindingReview;
$findingReview->decision = 'No';
$findingReview->review_id = 4;
$findingReview->rank = '1';
$findingReview->save();
$findingReview = new \MuBench\ReviewSite\Models\FindingReview;
$findingReview->decision = 'Yes';
$findingReview->review_id = 5;
$findingReview->rank = '1';
$findingReview->save();


echo 'Creating Violation Types<br/>';
$type = new \MuBench\ReviewSite\Models\Type;
Schema::dropIfExists($type->getTable());
Schema::create($type->getTable(), function(Blueprint $table){
   $table->increments('id');
   $table->text('name');
});
$type->name = 'missing/call';
$type->save();

echo 'Creating Violation Types for metadata misuses<br/>';
Schema::dropIfExists('metadata_types');
Schema::create('metadata_types', function (Blueprint $table){
    $table->increments('id');
    $table->integer('metadata_id');
    $table->integer('type_id');
});
$capsule->table('metadata_types')->insert(array('metadata_id' => 1, 'type_id' => 1));

echo 'Creating Violation Types for finding review<br/>';
Schema::dropIfExists('finding_review_types');
Schema::create('finding_review_types', function (Blueprint $table){
    $table->increments('id');
    $table->integer('finding_review_id');
    $table->integer('type_id');
});
$capsule->table('finding_review_types')->insert(array('finding_review_id' => 1, 'type_id' => 1));


echo 'Creating Tags<br/>';
$tag = new \MuBench\ReviewSite\Models\Tag;
Schema::dropIfExists($tag->getTable());
Schema::create($tag->getTable(), function(Blueprint $table){
   $table->increments('id');
   $table->text('name');
});
$tag->name = 'test-dataset';
$tag->save();
$tag = new \MuBench\ReviewSite\Models\Tag;
$tag->name = 'test-dataset2';
$tag->save();

echo 'Creating MisuseTag<br/>';
Schema::dropIfExists('misuse_tags');
Schema::create('misuse_tags', function(Blueprint $table){
   $table->increments('id');
   $table->integer('misuse_id');
   $table->integer('tag_id');
   $table->unique(['tag_id', 'misuse_id']);
});
$capsule->table('misuse_tags')->insert(array('misuse_id' => 3, 'tag_id' => 1));
$capsule->table('misuse_tags')->insert(array('misuse_id' => 1, 'tag_id' => 2));
$capsule->table('misuse_tags')->insert(array('misuse_id' => 3, 'tag_id' => 2));
$capsule->table('misuse_tags')->insert(array('misuse_id' => 2, 'tag_id' => 2));
