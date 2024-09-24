<?php

namespace Anodio\Http\Config;

use Anodio\Core\AttributeInterfaces\AbstractConfig;
use Anodio\Core\Attributes\Config;
use Anodio\Core\Configuration\Env;

//#[Config('http_config')]
class HttpConfig extends AbstractConfig
{
//    #[Env('REDIS_CONNECTION_STRING_FOR_SESSION_STORE', 'redis://redis:6379/1')]
    public string $redisConnectionStringForSessionStore;
}
