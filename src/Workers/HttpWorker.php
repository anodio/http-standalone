<?php

namespace Anodio\Http\Workers;

use Anodio\Core\ContainerManagement\ContainerManager;
use Anodio\Core\ContainerStorage;
use Anodio\Http\Config\WorkerConfig;
use Anodio\Supervisor\Clients\SupervisorClient;
use Anodio\Supervisor\SignalControl\SignalController;
use DI\Attribute\Inject;
use Swow\Buffer;
use Swow\Channel;
use Swow\Coroutine;
use Swow\Psr7\Server\Server;
use Swow\Psr7\Server\ServerConnection;
use Swow\Socket;
use Swow\SocketException;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpWorker
{
    #[Inject]
    public ?WorkerConfig $workerConfig = null;

    private $queriesGotCount = 0;

    protected function createServer(int $port): Server {
        $bindFlag = Socket::BIND_FLAG_NONE;

        $server = new Server(Socket::TYPE_TCP);
        $server->bind('0.0.0.0', $port, $bindFlag)->listen();
        echo json_encode(['msg'=>'Http worker starting at 0.0.0.0:'.$port]).PHP_EOL;
        return $server;
    }

    private function startSendingStats() {
        Coroutine::run(function (): void {
            $supervisorClient = SupervisorClient::getInstance();
            while(true) {
                $stats = [
                    'command' => 'workerStats',
                    'stats' => [
                        'memory' => memory_get_usage(true),
                        'memory_peak' => memory_get_peak_usage(),
                        'queries_got'=> $this->queriesGotCount,
                    ],
                ];
                $stats['workerNumber'] = $this->workerConfig->workerNumber;
                $supervisorClient->send($stats, 'worker', $this->workerConfig->workerNumber);
                sleep(5);
            }

        });
    }

    public function run(): bool
    {
        if ($this->workerConfig->devMode) {
            Coroutine::run(function (): void {
                sleep(300);
                echo json_encode(['msg'=>'Http dev worker is stopping by maximum of 300 seconds']).PHP_EOL;
                SignalController::getInstance()->sendExitSignal(0);
            });
        }

        if (!$this->workerConfig->workerNumber) {
            throw new \Exception('WORKER_NUMBER is not set');
        }
        $server = $this->createServer($this->workerConfig->workerNumber+8080);
        $this->startSendingStats();
        $httpWorkerControlChannel = $this->createControlTCPServer($this->workerConfig->workerNumber);
        Coroutine::run(function(Server $server) {
            while (true) {
                try {
                    $connection = null;
                    $connection = $server->acceptConnection();
                    $this->queriesGotCount++;
                    Coroutine::run(function (ServerConnection $connection): void {
                        $container = ContainerManager::createContainer();
                        ContainerStorage::setContainer($container);
                        try {
                            $kernel = $container->get(HttpKernel::class);
                            $request = $this->marshalRequest($connection);

                            $response = $kernel->handle($request);
                            $swowResponse = $this->convertResponseToSwowResponse($response);
                        } catch (\Throwable $exception) {
                            $swowResponse = $this->formErrorResponse($exception);
                        } finally {
                            if (isset($kernel) && isset($request) && isset($response)) {
                                $kernel->terminate($request, $response);
                            }
                            ContainerStorage::removeContainer();
                        }
                        $connection->sendHttpResponse($swowResponse);
                        $connection->close();
                        if ($this->workerConfig->devMode) {
                            echo json_encode(['msg'=>'Http dev worker is done and stopping']).PHP_EOL;
                            SignalController::getInstance()->sendExitSignal(0);
                        }
                    }, $connection);
                } catch (\Exception $exception) {
                    echo json_encode(['msg'=>'Http server error: '.$exception->getMessage()]).PHP_EOL;
                    if ($this->workerConfig->devMode) {
                        echo json_encode(['msg'=>'Http dev worker got error and stopping']).PHP_EOL;
                        SignalController::getInstance()->sendExitSignal(1);
                    }
                }
            }
        }, $server);
        return true;
    }

    public function marshalRequest(ServerConnection $connection) {
        $request = $connection->recvHttpRequest();
        $convertedHeaders = [];
        foreach ($request->getHeaders() as $key => $header) {
            $convertedHeaders['HTTP_' . $key] = $header[0];
        }
        $serverParams = array_merge([
            'REQUEST_URI' => $request->getUri()->getPath(),
            'REQUEST_METHOD' => $request->getMethod(),
            'QUERY_STRING' => $request->getUri()->getQuery(),
        ], $request->getServerParams(), $convertedHeaders);
        $parsedBody = $this->buildNestedArrayFromParsedBody($request->getParsedBody());
        return new \Symfony\Component\HttpFoundation\Request(
            query: $request->getQueryParams(),
            request: $parsedBody,
            attributes: [...$request->getAttributes(), 'transport'=>'http'],
            cookies: $request->getCookieParams(),
            files: $request->getUploadedFiles(),
            server: $serverParams,
            content: $request->getBody()->getContents()
        );
    }

    private function convertResponseToSwowResponse(\Symfony\Component\HttpFoundation\Response $response): \Swow\Psr7\Message\Response {
        $swowResponse = new \Swow\Psr7\Message\Response();
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->getBody()->write($response->getContent());
        return $swowResponse;
    }

    private function formErrorResponse(\Throwable $e): \Swow\Psr7\Message\Response
    {
        $response = new \Swow\Psr7\Message\Response();
        $response->setStatus(500);
        echo json_encode(['msg'=>'Http server error: '.$e->getMessage().' File: '.$e->getFile().' Line:'.$e->getLine()]).PHP_EOL;
        $response->getBody()->write($e->getMessage());
        return $response;
    }

    public function buildNestedArrayFromParsedBody(array $parsedBody) {

        $result = [];
        foreach ($parsedBody as $key => $value) {
            $keys = explode('[', $key);
            $keys = array_map(fn($key) => str_replace(']', '', $key), $keys);
            $nestedArray = [];
            $nestedArray[$keys[count($keys)-1]] = $value;
            for ($i = count($keys)-2; $i >= 0; $i--) {
                $nestedArray = [$keys[$i] => $nestedArray];
            }
            $result = array_merge_recursive($result, $nestedArray);
        }
        return $result;
    }

        private function createControlTCPServer(int $workerNumber): Channel {
        $workerControlPort = $workerNumber+7080;
        $controlChannel = new Channel();
        Coroutine::run(function(Channel $controlChannel, int $workerControlPort) {
            $server = new Socket(Socket::TYPE_TCP);
            $server->bind('0.0.0.0', $workerControlPort)->listen();
            while (true) {
                $connection = $server->accept();
                $buffer = new Buffer(Buffer::COMMON_SIZE);
                try {
                    while (true) {
                        $length = $connection->recv($buffer);
                        if ($length === 0) {
                            break;
                        }
                        $message = $buffer->read(length: $length);
                        $messageExploded = explode('}{', $message);
                        if (count($messageExploded)>1) {
                            $count = count($messageExploded);
                            foreach ($messageExploded as $key=>$oneMessage) {
                                if ($key==0) {
                                    $oneMessage = $oneMessage.'}';
                                } elseif ($key==$count-1) {
                                    $oneMessage = '{'.$oneMessage;
                                } else {
                                    $oneMessage = '{'.$oneMessage.'}';
                                }
                                $controlChannel->push(json_decode($oneMessage, true, 512, JSON_THROW_ON_ERROR));
                            }
                        } else {
                            $controlChannel->push(json_decode($message, true, 512, JSON_THROW_ON_ERROR));
                        }
                    }
                } catch (SocketException $exception) {
                    echo "No.{$connection->getFd()} goaway! {$exception->getMessage()}" . PHP_EOL;
                }
            }
        }, $controlChannel, $workerControlPort);
        return $controlChannel;
    }
}
