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
    $table->string('project_id', 30);
    $table->string('version_id', 30);
    $table->string('runtime', 30);
    $table->string('number_of_findings', 30);
    $table->string('result', 30);
    $table->text('additional_stat')->nullable();
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$run->experiment_id = $experiment->id;
$run->project_id = 'mubench';
$run->version_id = '42';
$run->result = 'success';
$run->number_of_findings = '2';
$run->runtime = '3.40';
$run->save();
$run = new \MuBench\ReviewSite\Models\Run;
$run->setDetector($detector);
$run->experiment_id = $experiment->id;
$run->project_id = 'mubench_2';
$run->version_id = '43';
$run->result = 'success';
$run->number_of_findings = '0';
$run->runtime = '3.40';
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
    $table->string('project_id', 30);
    $table->string('version_id', 30);
    $table->string('misuse', 30);
    $table->integer('rank');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$finding->experiment_id = $experiment->id;
$finding->misuse_id = 1;
$finding->project_id = 'mubench';
$finding->version_id = '42';
$finding->misuse = '1';
$finding->rank = 1;
$finding->save();

echo 'Creating misuses (metadata)<br/>';
$metadata = new \MuBench\ReviewSite\Models\Metadata;
Schema::dropIfExists($metadata->getTable());
Schema::create($metadata->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->string('project_id', 30);
    $table->string('version_id', 30);
    $table->string('misuse_id', 30);
    $table->text('description');
    $table->text('fix_description');
    $table->text('file');
    $table->text('method');
    $table->text('diff_url');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');

    $table->unique(['project_id', 'version_id', 'misuse_id']);
});
$metadata->project_id = 'mubench';
$metadata->version_id = '42';
$metadata->misuse_id = '1';
$metadata->description = 'desc';
$metadata->fix_description = 'fix-desc';
$metadata->file = '/some/file.ext';
$metadata->method = 'method(P)';
$metadata->diff_url = 'http://diff/url';
$metadata->save();
$metadata = new \MuBench\ReviewSite\Models\Metadata;
$metadata->project_id = 'mubench';
$metadata->version_id = '42';
$metadata->misuse_id = '2';
$metadata->description = 'desc';
$metadata->fix_description = 'fix-desc';
$metadata->file = '/some/file.ext';
$metadata->method = 'method(P)';
$metadata->diff_url = 'http://diff/url';
$metadata->save();

echo 'Creating misuses<br/>';
$misuse = new \MuBench\ReviewSite\Models\Misuse;
Schema::dropIfExists($misuse->getTable());
Schema::create($misuse->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->integer('metadata_id');
    $table->integer('run_id');
    $table->string('project_id', 30);
    $table->string('version_id', 30);
    $table->string('misuse_id', 30);
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});

$misuse->metadata_id = 1;
$misuse->project_id = 'mubench';
$misuse->version_id = '42';
$misuse->misuse_id = '1';
$misuse->run_id = 1;
$misuse->save();
$misuse = new \MuBench\ReviewSite\Models\Misuse;
$misuse->metadata_id = 2;
$misuse->project_id = 'mubench';
$misuse->version_id = '42';
$misuse->misuse_id = '2';
$misuse->run_id = 1;
$misuse->save();

echo 'Creating review<br/>';
$review = new \MuBench\ReviewSite\Models\Review;
Schema::dropIfExists($review->getTable());
Schema::create($review->getTable(), function(Blueprint $table){
    $table->increments('id');
    $table->integer('misuse_id');
    $table->integer('user_id');
    $table->text('comment');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$review->misuse_id = 1;
$review->user_id = 1;
$review->comment = "This is a test comment!";
$review->save();

echo 'Creating user<br/>';
$user = new \MuBench\ReviewSite\Models\User;
Schema::dropIfExists($user->getTable());
Schema::create($user->getTable(), function(Blueprint $table){
    $table->increments('id');
    $table->text('name');
});
$user->name = 'reviewer';
$user->save();

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
$metadataType = new \MuBench\ReviewSite\Models\MetadataType;
Schema::dropIfExists($metadataType->getTable());
Schema::create($metadataType->getTable(), function (Blueprint $table){
    $table->increments('id');
    $table->integer('metadata_id');
    $table->integer('type_id');
});
$metadataType->type_id = 1;
$metadataType->metadata_id = 1;
$metadataType->save();

echo 'Creating Tags<br/>';
$tag = new \MuBench\ReviewSite\Models\Tag;
Schema::dropIfExists($tag->getTable());
Schema::create($tag->getTable(), function(Blueprint $table){
   $table->increments('id');
   $table->text('name');
});
$tag->name = 'test-dataset';
$tag->save();

echo 'Creating MisuseTag<br/>';
$misuseTag = new \MuBench\ReviewSite\Models\MisuseTag;
Schema::dropIfExists($misuseTag->getTable());
Schema::create($misuseTag->getTable(), function(Blueprint $table){
   $table->increments('id');
   $table->integer('misuse_id');
   $table->integer('tag_id');
});
$misuseTag->misuse_id = 2;
$misuseTag->tag_id = 1;
$misuseTag->save();

