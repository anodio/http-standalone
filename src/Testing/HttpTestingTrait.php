<?php

namespace Anodio\Http\Testing;

trait HttpTestingTrait
{
    public function request() {
        return new HttpTestingBuilder();
    }
    public function get(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri)
            ->withQueryParameters($params)
            ->withHeaders($headers)
            ->withOptions($options);
        return $requestBuilder->get();
    }

    public function getJson(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->acceptJson();
        $requestBuilder->withQueryParameters($params);
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->get();
    }

    public function post(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->withBody(http_build_query($params));
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->post();
    }

    public function postJson(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->withJson($params);
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->post();
    }

    public function put(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->withBody(http_build_query($params));
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->put();
    }

    public function putJson(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->withJson($params);
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->put();
    }

    public function delete(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
        $requestBuilder->withQueryParameters($params);
        $requestBuilder->withHeaders($headers);
        $requestBuilder->withOptions($options);
        return $requestBuilder->delete();
    }
}