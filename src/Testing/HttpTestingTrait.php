<?php

namespace Anodio\Http\Testing;

trait HttpTestingTrait
{
    public function internalRequest() {
        return new HttpTestingBuilder();
    }
    public function get(string $uri, array $params = [], array $headers = [], array $options = [])
    {
        $requestBuilder = new HttpTestingBuilder();
        $requestBuilder->url($uri);
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
}