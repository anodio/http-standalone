<?php

namespace Anodio\Http\Server;

use Anodio\Core\ContainerManagement\ContainerManager;
use Anodio\Http\Config\HttpServerConfig;
use DI\Attribute\Inject;
use Swow\Coroutine;
use Swow\Psr7\Server\Server;
use Swow\Psr7\Server\ServerConnection;
use Swow\Socket;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class HttpServer
{

    #[Inject]
    private HttpServerConfig $config;

    protected function createServer(): Server {
        $host = $this->config->host;
        $port = $this->config->port;
        $bindFlag = Socket::BIND_FLAG_NONE;

        $server = new Server(Socket::TYPE_TCP);
        $server->bind($host, $port, $bindFlag)->listen();
        echo json_encode(['msg'=>'Http server starting at '.$host.':'.$port]).PHP_EOL;
        return $server;
    }

    public function run(): bool
    {
        $server = $this->createServer();
        while (true) {
            try {
                $connection = null;
                $connection = $server->acceptConnection();
                Coroutine::run(function (ServerConnection $connection): void {
                    $container = ContainerManager::createContainer();
                    try {
                        $kernel = $container->get(HttpKernel::class);
                        $request = $this->marshalRequest($connection);

                        $context = new RequestContext();
                        $context->fromRequest($request);
                        $matcher = new UrlMatcher($container->get(RouteCollection::class), $context);

                        $attributes = $matcher->match($request->getPathInfo());
                        $request->attributes->add($attributes);

                        $response = $kernel->handle($request);
                        $swowResponse = $this->convertResponseToSwowResponse($response);
                    } catch (\Throwable $exception) {
                        $trap = $container->get(\Anodio\Http\Trap\HttpExceptionTrap::class);
                        $trap->report($exception);
                        $response = $trap->getResponse();
                        $swowResponse = $this->convertResponseToSwowResponse($response);
                    } finally {
                        if (!isset($swowResponse)) {
                            $swowResponse = $this->formErrorResponse($exception);
                        }
                        if (isset($kernel)) {
                            $kernel->terminate($request, $response);
                        }
                    }
                    $connection->sendHttpResponse($swowResponse);
                    $connection->close();
                    echo 'Memory usage before: '.memory_get_usage()/1024/1024 . ' MB'.PHP_EOL;
                    gc_collect_cycles();
                    echo 'Memory usage after: '.memory_get_usage()/1024/1024 . ' MB'.PHP_EOL;
                }, $connection);
            } catch (\Exception $exception) {
                echo json_encode(['msg'=>'Http server error: '.$exception->getMessage()]).PHP_EOL;
            }
        }

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

    public static function buildNestedArrayFromParsedBody(array $parsedBody) {

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

    private function convertResponseToSwowResponse(\Symfony\Component\HttpFoundation\Response $response): \Swow\Psr7\Message\Response {
        $swowResponse = new \Swow\Psr7\Message\Response();
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->getBody()->write($response->getContent());
        return $swowResponse;
    }

    private function formErrorResponse(\Exception $e): \Swow\Psr7\Message\Response
    {
        $response = new \Swow\Psr7\Message\Response();
        $response->setStatus($e->getCode());
        $response->getBody()->write($e->getMessage());
        return $response;
    }

}