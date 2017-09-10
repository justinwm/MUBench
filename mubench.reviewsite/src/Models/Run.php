<?php

namespace MuBench\ReviewSite\Models;


class Run extends DetectorDependent
{
    protected  function getTableName(Detector $detector)
    {
        return 'stats_' . $detector->id;
    }
}
