<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\Type;
use Slim\Http\Request;
use Slim\Http\Response;

class TypeController extends Controller
{

    public function getTypes(Request $request, Response $response, array $args){
        $results = array();
        foreach(Type::all() as $type){
            $results[$type->name] = count($type->metadata);
        }
        return $this->renderer->render($response, 'type_stats.phtml', ['results' => $results]);
    }

}
