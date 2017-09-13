<?php

namespace MuBench\ReviewSite\Controller;


use MuBench\ReviewSite\Models\Detector;
use MuBench\ReviewSite\Models\Experiment;
use MuBench\ReviewSite\Models\Run;
use Slim\Http\Request;
use Slim\Http\Response;

class RunsController extends Controller
{
    public function getIndex(Request $request, Response $response, array $args)
    {
        $experiment_id = $args['e'];
        $detector_id = $args['d'];

        $experiment = Experiment::find($experiment_id);
        $detector = Detector::find($detector_id);

        $runs = Run::of($detector)->in($experiment)->get();

        return $this->renderer->render($response, 'detector.phtml', [
            'experiment' => $experiment,
            'detector' => $detector,
            'runs' => $runs
        ]);
    }
}
