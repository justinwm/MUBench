<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Misuse extends Model
{
    public function metadata()
    {
        return $this->hasOne(Metadata::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function misuse_tags()
    {
        return $this->hasMany(MisuseTag::class);
    }

    // TODO: somehow link misuse with runs (custom table name problem)

    // TODO: implement all methods

    public function getShortId()
    {
        // TODO: connect with runs table
        $project = $this->project_id;
        $id = $this->misuse_id;
        return substr($id, 0, strlen($project)) === $project ? substr($id, strlen($project) + 1) :
            $id;
    }

    public function getViolationTypes()
    {
        return $this->metadata->types;
    }

    public function getReviewState()
    {
        return "";
    }

    public function hasPotentialHits()
    {
        return [];
    }

    public function hasReviewed($reviewer_name)
    {
        return false;
    }

    public function hasConclusiveReviewState()
    {
        return true;
    }

}
