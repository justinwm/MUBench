<?php

namespace MuBench\ReviewSite\Models;


use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{

    protected $fillable = ['misuse_muid', 'project_muid', 'version_muid', 'description', 'fix_description', 'file', 'method', 'diff_url'];

    public function patterns()
    {
        return $this->hasMany(Pattern::class);
    }

    public function violation_types()
    {
        $types = [];
        foreach(Type::all() as $type){
            if($type->metadata->where('id', $this->id)->first()){
                $types[] = $type;
            }
        }
        return $types;
    }

}
