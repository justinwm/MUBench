<?php

use PHPUnit\Framework\TestCase;
use Monolog\Logger;
use Pixie\QueryBuilder\QueryBuilderHandler;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;

class SlimTestCase extends TestCase
{
    protected $app;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /** @var \Illuminate\Database\Capsule\Manager */
    protected $db;

    /** @var  \Illuminate\Support\Facades\Schema */
    protected $schema;

    public function setUp(){
        $settings = require __DIR__ . '/../src/settings.php';
        $app = new \Slim\App($settings);

        require __DIR__ . '/../src/dependencies.php';
        $this->logger = new \Monolog\Logger("test");
        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->db = $capsule;
        $this->schema = $capsule->schema();
        // The schema accesses the database through the app, which we do not have in
        // this context. Therefore, use an array to provide the database. This seems
        // to work fine.
        /** @noinspection PhpParamsInspection */
        \Illuminate\Support\Facades\Schema::setFacadeApplication(["db" => $capsule]);

        require __DIR__ . '/create_test_database.php';
        $container = $app->getContainer();
        $container['database2'] = $this->db;
        $container['schema'] = $this->schema;

        require __DIR__ . '/../src/routes.php';

        $this->app = $app;
        $this->container = $container;
    }

    public function get($path, $data = array(), $optionalHeaders = array()){
        return $this->request('get', $path, $data, $optionalHeaders);
    }

    private function request($method, $path, $data = array(), $optionalHeaders = array()){
        //Make method uppercase
        $method = strtoupper($method);
        $options = array(
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $path
        );
        if ($method === 'GET') {
            $options['QUERY_STRING'] = http_build_query($data);
        } else {
            $params  = json_encode($data);
        }
        // Prepare a mock environment
        $env = Environment::mock(array_merge($options, $optionalHeaders));
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $serverParams = $env->all();
        $body = new RequestBody();
        // Attach JSON request
        if (isset($params)) {
            $headers->set('Content-Type', 'application/json;charset=utf8');
            $body->write($params);
        }
        $this->request  = new Request($method, $uri, $headers, array(), $serverParams, $body);
        $response = new Response();
        // Invoke request
        $app = $this->app;
        $this->response = $app($this->request, $response);
        // Return the application output.
        return $this->response->getBody();
    }

    private function mySQLToSQLite($mysql){
        $lines = explode("\n", $mysql);
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            // remove all named keys, i.e., leave only PRIMARY keys
            if (strpos($lines[$i], 'KEY `') !== false) {
                $lines[$i] = "";
                $lines[$i - 1] = substr($lines[$i - 1], 0, -1); // remove trailing comma in previous line
            }
        }
        $sqlite = implode("\n", $lines);
        $sqlite = str_replace("AUTO_INCREMENT", "", $sqlite);
        $sqlite = str_replace("int(11)", "INTEGER", $sqlite);
        $sqlite = str_replace(" ENGINE=MyISAM  DEFAULT CHARSET=latin1;", ";", $sqlite);
        return $sqlite;
    }
}