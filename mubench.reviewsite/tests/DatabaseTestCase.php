<?php

use Monolog\Logger;
use MuBench\ReviewSite\DBConnection;
use PHPUnit\Framework\TestCase;
use Pixie\QueryBuilder\QueryBuilderHandler;

class DatabaseTestCase extends TestCase
{
    /** @var QueryBuilderHandler pdo */
    protected $pdo;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var DBConnection $db
     */
    protected $db;

    /** @var \Illuminate\Database\Capsule\Manager */
    protected $db2;

    /** @var  \Illuminate\Support\Facades\Schema */
    protected $schema;

    function setUp()
    {
        $connection = new \Pixie\Connection('sqlite', ['driver' => 'sqlite', 'database' => ':memory:']);
        $this->pdo = $connection->getQueryBuilder();
        $mysql_structure = file_get_contents(dirname(__FILE__) . "/../init_db.sql");
        $this->pdo->pdo()->exec($this->mySQLToSQLite($mysql_structure));
        $this->logger = new \Monolog\Logger("test");
        $this->db = new DBConnection($connection, $this->logger);

        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->db2 = $capsule;
        $this->schema = $capsule->schema();
        // The schema accesses the database through the app, which we do not have in
        // this context. Therefore, use an array to provide the database. This seems
        // to work fine.
        /** @noinspection PhpParamsInspection */
        \Illuminate\Support\Facades\Schema::setFacadeApplication(["db" => $capsule]);

        require __DIR__ . '/create_test_database.php';
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
