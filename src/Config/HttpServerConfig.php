<?php

namespace Anodio\Http\Config;

use Anodio\Core\AttributeInterfaces\AbstractConfig;
use Anodio\Core\Attributes\Config;
use Anodio\Core\Configuration\Env;
use Monolog\Handler\StreamHandler;

#[Config('http_server')]
class HttpServerConfig extends AbstractConfig
{
    #[Env('HTTP_SERVER_HOST', default: '0.0.0.0')]
    public string $host;

    #[Env('HTTP_SERVER_PORT', default: '8080')]
    public int $port;

}