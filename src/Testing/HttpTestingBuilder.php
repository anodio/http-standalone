<?php

namespace Anodio\Http\Testing;

use Anodio\Core\ContainerStorage;
use Anodio\HttpClient\HttpRequestBuilder;

class HttpTestingBuilder extends HttpRequestBuilder
{

    public function url(string $url): static {
        if (!str_starts_with($url, '/')) {
            throw new \Exception('Only relative urls are allowed');
        }
        $this->url = $url;
        return $this;
    }

    protected function createTestRequest(string $method): \Symfony\Component\HttpFoundation\Request {
        if (CONTAINER_NAME!='pest') {
            throw new \Exception('This method is only for pest tests');
        }

        $convertedHeaders = [];
        if (isset($this->options['headers'])) {
            foreach ($this->options['headers'] as $key => $header) {
                $convertedHeaders['HTTP_' . $key] = $header[0];
            }
        }

        $explodedUrl = explode('?', $this->url);

        $serverParams = array_merge([
            'REQUEST_URI' => $explodedUrl[0],
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $explodedUrl[1] ?? '',
        ], $convertedHeaders);
        return new \Symfony\Component\HttpFoundation\Request(
            query: $this->options['query'] ?? [],
            request: [],
            attributes: ['transport'=>'http'],
            cookies: $this->options['cookies'] ?? [],
            files: $this->options['files'] ?? [],
            server: $serverParams,
            content: $this->options['body'] ?? ''
        );
    }

    private function sendInnerRequestToCurrentKernel(string $method): \Symfony\Contracts\HttpClient\ResponseInterface {
        $container = ContainerStorage::getContainer();
        $kernel = $container->get('kernel');
        $request = $this->createTestRequest($method);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return $response;
    }

    public function get(): \Symfony\Contracts\HttpClient\ResponseInterface {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('GET');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function head(): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('HEAD');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function post(): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('POST');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function patch(): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('PATCH');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function put(): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('PUT');
        }else {
            throw new \Exception('Only for test/pest environment');
        }
    }

    public function delete(): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if (CONTAINER_NAME=='pest' && str_starts_with($this->url, '/')) {
            return $this->sendInnerRequestToCurrentKernel('DELETE');
        } else {
            throw new \Exception('Only for test/pest environment');
        }
    }
}