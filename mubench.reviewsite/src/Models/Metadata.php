<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    public function patterns()
    {
        return $this->hasMany('MuBench\Models\Pattern', 'misuse', 'misuse');
    }

    public function types()
    {
        return $this->hasMany(MetadataType::class);
    }

}