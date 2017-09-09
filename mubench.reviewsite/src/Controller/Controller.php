<?php

namespace MuBench\ReviewSite\Controller;

use MuBench\ReviewSite\Model\Experiment;
use Slim\Http\Request;
use Slim\Http\Response;

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

    protected function render(Request $request, Response $response, array $args, $template, array $params)
    {
        $params["user"] = $this->getUser($request);

        $params["site_base_url"] = htmlspecialchars($this->settings['site_base_url']);
        $params["public_url_prefix"] = $params["site_base_url"] . "index.php/";
        $params["private_url_prefix"] = $params["site_base_url"] . "index.php/private/";
        $params["api_url_prefix"] = $params["site_base_url"] . "index.php/api/";
        $params["uploads_url_prefix"] = $params["site_base_url"] . $this->settings['upload_path'];
        $params["url_prefix"] = $params["user"] ? $params["private_url_prefix"] : $params["public_url_prefix"];

        $path = $request->getUri()->getPath();
        $params["path"] = htmlspecialchars(strcmp($path, "/") === 0 ? "" : $path);
        $params["origin_param"] = htmlspecialchars("?origin=" . $params["path"]);
        $params["origin_path"] = htmlspecialchars($request->getQueryParam("origin", ""));

        if (array_key_exists('exp', $args)) {
            $params["experiment"] = Experiment::get($args["exp"]);
        } else {
            $params['experiment'] = null;
        }
        $params["experiments"] = Experiment::all();
        if (array_key_exists('detector', $args)) {
            $params["detector"] = $this->getDetector($args['detector'], $request, $response);
        }
        $params["detectors"] = [];
        foreach ($params["experiments"] as $experiment) { /** @var Experiment $experiment */
            $params["detectors"][$experiment->getId()] = $this->database->getDetectors($experiment->getId());
        }

        return $this->renderer->render($response, $template, $params);
    }

    private function getUser(Request $request)
    {
        $params = $request->getServerParams();
        return array_key_exists('PHP_AUTH_USER', $params) ? $params['PHP_AUTH_USER'] : "";
    }

    private function getDetector($detector_name, $request, $response)
    {
        try{
            return $this->database->getDetector($detector_name);
        }catch (\InvalidArgumentException $e){
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
}