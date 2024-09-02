<?php

namespace Anodio\Http\Commands;

use Anodio\Http\Workers\HttpWorker;
use Anodio\Supervisor\SignalControl\SignalController;
use DI\Attribute\Inject;
use Symfony\Component\Console\Command\Command;

#[\Anodio\Core\Attributes\Command('http:run-worker', description: 'Run http worker')]
class RunHttpWorker extends Command
{

    #[Inject]
    public HttpWorker $httpWorker;
    protected function configure(): void
    {
        $this->setDescription('Run http worker');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $this->runHttpWorker();
        return 0;
    }

    protected function runHttpWorker(): bool
    {
        $this->httpWorker->run();
        SignalController::getInstance()->control();
        return true;
    }
}
