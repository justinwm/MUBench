<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Detector extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['muid', 'id'];
    protected $primaryKey = 'muid';

    public static function withFindings(Experiment $experiment)
    {
        return Detector::all()->filter(function(Detector $detector) use ($experiment) {
            return $detector->hasResults($experiment);
        })->sortBy('muid');
    }

    public function hasResults(Experiment $experiment)
    {
        return Finding::of($this)->in($experiment)->exists();
    }
}
