<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Misuse extends Model
{
    protected $table = 'metadata';

    public function patterns()
    {
        return $this->hasMany('MuBench\Models\Pattern', 'misuse', 'misuse');
    }
}
