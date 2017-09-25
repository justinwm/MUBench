<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    public $timestamps = false;

    public function review()
    {
        return $this->hasMany(Review::class);
    }

}