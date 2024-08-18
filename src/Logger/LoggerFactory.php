<?php

namespace Anodio\Http\Logger;

use Anodio\Http\Config\HttpServerConfig;
use DI\Attribute\Inject;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{

    #[Inject]
    public HttpServerConfig $config;

    public function createLogger() {
        $logger = new Logger('HttpServerLogger');
        if (empty($this->config->logDestination)) {
            $logDestination = 'php://stdout';
        } else {
            $logDestination = $this->config->logDestination;
        }
        if (empty($this->config->logLevel)) {
            $logLevel = \Monolog\Level::Debug;
        } else {
            $logLevel = $this->config->logLevel;
        }
        if (empty($this->config->logHandler)) {
            $logger->pushHandler(new StreamHandler('php://stdout', $logLevel));
        }
        $logger->pushHandler(new $this->config->logHandler($logDestination, $logLevel));
        return $logger;
    }
}