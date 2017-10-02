<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    public function patterns()
    {
        return $this->hasMany(Pattern::class);
    }

    public function violation_types()
    {
        return $this->belongsToMany(Type::class, 'metadata_types', 'type_id', 'metadata_id');
    }

}
