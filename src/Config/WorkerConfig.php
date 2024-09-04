<?php

namespace Anodio\Http\Config;

use Anodio\Core\AttributeInterfaces\AbstractConfig;
use Anodio\Core\Attributes\Config;
use Anodio\Core\Configuration\Env;

#[Config('worker')]
class WorkerConfig extends AbstractConfig
{
    #[Env('WORKER_NUMBER', null, 'You dont need to set this value manually.')]
    public ?int $workerNumber;

    #[Env('DEV_MODE', false)]
    public bool $devMode;
}
