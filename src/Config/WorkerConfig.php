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

    #[Env('GC_WORKER_EVERY_MINUTES', 1)]
    public int $gcWorkerEveryMinutes;

    //experimental feature. Better to not enable for now.
    #[Env('HTTP_WORKER_CONTAINER_PRELOADED_COUNT', 0)]
    public int $httpWorkerContainerPreloadedCount = 0;
}
