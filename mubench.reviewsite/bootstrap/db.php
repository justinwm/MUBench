<?php

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// The schema accesses the database through the app, which we do not have in
// this context. Therefore, use an array to provide the database. This seems
// to work fine.
/** @noinspection PhpParamsInspection */
\Illuminate\Support\Facades\Schema::setFacadeApplication(["db" => $capsule]);