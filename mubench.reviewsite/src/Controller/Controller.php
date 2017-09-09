<?php

namespace MuBench\ReviewSite\Controller;

class Controller
{
    private $container;

    public function __construct($container)
    {

        $this->container = $container;
    }

    public function __get($property)
    {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
        return null;
    }
}
