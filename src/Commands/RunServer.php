<?php

namespace Bicycle\Http\Commands;

use Bicycle\Http\Server\HttpServer;
use DI\Attribute\Inject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Bicycle\Core\Attributes\Command(
    name: 'run:server',
    description: 'Run http server'
)]
class RunServer extends Command
{
    #[Inject]
    private HttpServer $httpServer;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->httpServer->run();
        return 0;
    }
}