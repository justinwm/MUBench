<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    public $timestamps = false;

    public function metadata()
    {
        return $this->hasMany(Metadata::class, 'version_id', 'version_id');
    }
}