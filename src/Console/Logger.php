<?php

namespace OffeneVergaben\Console;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger {

    protected $directory = BASE_PATH . DIRECTORY_SEPARATOR . 'logs';

    protected $fileName = 'console.log';

    protected $logger;

    public function __construct() {
        if(!file_exists($this->directory)) {
            mkdir($this->directory);
        }

        $filePath = $this->directory . DIRECTORY_SEPARATOR . $this->fileName;

        if (!file_exists($filePath)) {
            touch($filePath);
        }

        // Create a very simple one channel logger
        $this->logger = new MonologLogger('console_log');
        $this->logger->pushHandler(new StreamHandler($filePath, MonologLogger::DEBUG));
    }

    public function getLogger() {
        return $this->logger;
    }
}