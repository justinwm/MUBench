<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{

    protected $fillable = ['name'];

    public function misuses()
    {
        return $this->belongsToMany(Misuse::class, 'misuse_tags', 'tag_id', 'misuse_id');
    }

    public $timestamps = false;
}
