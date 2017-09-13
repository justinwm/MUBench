<?php

namespace MuBench\ReviewSite\Models;


class Run extends DetectorDependent
{
    protected  function getTableName(Detector $detector)
    {
        return 'stats_' . $detector->id;
    }

    public function misuses()
    {
        return $this->hasMany(Misuse::class, 'project_id', 'project_id')->where('version_id', $this->version_id);
    }
}
