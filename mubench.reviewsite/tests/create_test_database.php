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
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$run->experiment_id = $experiment->id;
$run->project_id = 'mubench';
$run->version_id = '42';
$run->save();

echo 'Creating findings<br/>';
$finding = new \MuBench\ReviewSite\Models\Finding;
$finding->setDetector($detector);
Schema::dropIfExists($finding->getTable());
Schema::create($finding->getTable(), function(Blueprint $table) {
    $table->increments('id');
    $table->integer('experiment_id');
    $table->string('project_id', 30);
    $table->string('version_id', 30);
    $table->string('misuse_id', 30);
    $table->integer('rank');
    $table->dateTime('created_at');
    $table->dateTime('updated_at');
});
$finding->experiment_id = $experiment->id;
$finding->project_id = 'mubench';
$finding->version_id = '42';
$finding->misuse_id = '1';
$finding->rank = 1;
$finding->save();

echo 'Creating misuses (metadata)<br/>';
$misuse = new \MuBench\ReviewSite\Models\Misuse;
Schema::dropIfExists($misuse->getTable());
Schema::create($misuse->getTable(), function(Blueprint $table) {
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
$misuse->project_id = 'mubench';
$misuse->version_id = '42';
$misuse->misuse_id = '1';
$misuse->description = 'desc';
$misuse->fix_description = 'fix-desc';
$misuse->file = '/some/file.ext';
$misuse->method = 'method(P)';
$misuse->diff_url = 'http://diff/url';
$misuse->save();
