<?php

namespace OffeneVergaben\Console;

use OffeneVergaben\Console\Database\ConnectionInterface;
use Dotenv\Dotenv;
use Symfony\Component\Console\Application as Console;

/**
 * The application god object
 */
class Application
{
    const NAME    = 'Offene Vergaben Console';
    const VERSION = '0.1 (dev)';

    private static $instance;

    protected $console;

    protected $env;

    private function __construct() {
        // Create the Console
        $this->console = new Console(self::NAME,self::VERSION);

        // load environment configuration settings
        $env = Dotenv::createImmutable(BASE_PATH);
        $env->load();
        $this->env = $env;

        // add logger
        $this->logger = new Logger();

        // add database
        $this->database = $this->createDatabaseConnection();

        // set default timezone
        date_default_timezone_set('Europe/Vienna');
    }

    public function sigIntHandler($sigNo) {
        echo "sig handler called \n";
        var_dump($sigNo);
        echo "exit \n";

        $this->database->disconnect();

        exit;
    }

    /**
     * @return Application
     */
    public static function getInstance() {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return Application
     */
    public static function app() {
        return static::getInstance();
    }

    /**
     * Run this app.
     *
     * @throws \Exception
     */
    public function run() {
        $this->console->run();
    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     */
    public function addCommand($command) {
        $this->console->add($command);
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger() {
        return $this->logger->getLogger();
    }

    /**
     * @return ConnectionInterface
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Try to instantiate database class from string
     *
     * @return ConnectionInterface
     */
    protected function createDatabaseConnection() {
        $connection = getenv('DB_CONNECTION');

        if (!$connection) {
            return null;
        }

        $namespace  = 'OffeneVergaben\Console\Database';
        $className  = ucfirst(strtolower($connection)) . 'Connection';
        $fqn        = $namespace . '\\' . $className;

        return new $fqn;
    }
}