<?php
/**
 * Created by PhpStorm.
 * User: jonasschlitzer
 * Date: 30.09.17
 * Time: 15:25
 */

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Snippet extends Model
{
    public $timestamps = false;
    public $fillable = ['project_muid', 'version_muid', 'misuse_muid', 'snippet', 'line', 'rank'];
}
