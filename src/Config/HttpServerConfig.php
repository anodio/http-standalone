<?php

namespace Bicycle\Http\Config;

use Bicycle\Core\AttributeInterfaces\AbstractConfig;
use Bicycle\Core\Attributes\Config;
use Bicycle\Core\Configuration\Env;
use Monolog\Handler\StreamHandler;

#[Config('http_server')]
class HttpServerConfig extends AbstractConfig
{
    #[Env('HTTP_SERVER_HOST', default: '0.0.0.0')]
    public string $host;

    #[Env('HTTP_SERVER_PORT', default: '8080')]
    public int $port;

    #[Env('HTTP_SERVER_LOG_HANDLER', default: StreamHandler::class)]
    public string $logHandler;

    #[Env('HTTP_SERVER_LOG_LEVEL', default: 'DEBUG')]
    public string $logLevel;

    #[Env('HTTP_SERVER_LOG_DESTINATION', default: 'php://stdout')]
    public string $logDestination = 'php://stdout';


}