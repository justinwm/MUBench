<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class MetadataType extends Model
{
    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

}