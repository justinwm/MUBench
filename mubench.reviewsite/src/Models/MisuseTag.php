<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class MisuseTag extends Model
{
    public $timestamps = false;

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}