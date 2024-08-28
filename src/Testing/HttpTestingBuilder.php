<?php

namespace Anodio\Http\Testing;

use Anodio\Core\ContainerStorage;
use Anodio\HttpClient\HttpRequestBuilder;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpTestingBuilder extends HttpRequestBuilder
{

    public function url(string $url): static {
        if (!str_starts_with($url, '/')) {
            throw new \Exception('Only relative urls are allowed');
        }
        $this->url = $url;
        return $this;
    }

    /**
     * @internal for inside testing purposes only
     * @param string $method
     * @return \Symfony\Component\HttpFoundation\Request
     * @throws \Exception
     */
    public function createTestSymfonyRequest(string $method): \Symfony\Component\HttpFoundation\Request {
        if (CONTAINER_NAME!='pest') {
            throw new \Exception('This method is only for pest tests');
        }

        $convertedHeaders = [];
        $needToSendJson = false;
        if (isset($this->options['headers'])) {
            foreach ($this->options['headers'] as $key => $header) {
                if (strtolower($key) === 'content-type' && strtolower($header) === 'application/json') {
                    $needToSendJson = true;
                }
                $convertedHeaders['HTTP_' . $key] = $header;
            }
        }
        $data = [];
        if (in_array($method, ['POST','PATCH', 'PUT']) && $needToSendJson && isset($this->options['body']) && $this->options['body']) {
            $data = json_decode($this->options['body'], true) ?? [];
        }

        $explodedUrl = explode('?', $this->url);

        $serverParams = array_merge([
            'REQUEST_URI' => $explodedUrl[0],
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $explodedUrl[1] ?? '',
        ], $convertedHeaders);
        return new \Symfony\Component\HttpFoundation\Request(
            query: $this->options['query'] ?? [],
            request: $data,
            attributes: ['transport'=>'http'],
            cookies: $this->options['cookies'] ?? [],
            files: $this->options['files'] ?? [],
            server: $serverParams,
            content: $this->options['body'] ?? ''
        );
    }

    private function sendInnerRequestToCurrentKernel(string $method): ResponseTestContainer {
        $container = ContainerStorage::getContainer();
        $kernel = $container->get(HttpKernel::class);
        $request = $this->createTestSymfonyRequest($method);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return new ResponseTestContainer($response);
    }

    public function get(): ResponseTestContainer {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('GET');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function head(): ResponseTestContainer
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('HEAD');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function post(): ResponseTestContainer
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('POST');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function patch(): ResponseTestContainer
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('PATCH');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function put(): ResponseTestContainer
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('PUT');
        }else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function delete(): ResponseTestContainer
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('DELETE');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }
}
