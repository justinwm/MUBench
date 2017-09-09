<?php

namespace MuBench\ReviewSite\Controller;

use Slim\Http\Request;
use Slim\Http\Response;

class ExperimentsController extends Controller
{
    public function index(Request $request, Response $response, array $args)
    {
        return $this->render($request, $response, $args, 'index.phtml', []);
    }
}
