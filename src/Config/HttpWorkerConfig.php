<?php

namespace Anodio\Http\Config;

use Anodio\Core\AttributeInterfaces\AbstractConfig;
use Anodio\Core\Attributes\Config;
use Anodio\Core\Configuration\Env;
use Anodio\Core\Configuration\EnvRequiredNotEmpty;

#[Config('httpWorker')]
class HttpWorkerConfig extends AbstractConfig
{
    #[Env('HTTP_WORKER_NUMBER', null, 'You dont need to set this value manually.')]
    public ?int $workerNumber;

    #[Env('DEV_MODE', false)]
    public bool $devMode;
}
