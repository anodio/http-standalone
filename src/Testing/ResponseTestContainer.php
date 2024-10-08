<?php

namespace Anodio\Http\Testing;

use Symfony\Component\HttpFoundation\Response;

class ResponseTestContainer
{
    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getContent() {
        return $this->response->getContent();
    }

    /**
     * @deprecated Use getContent() instead
     * @return false|string
     */
    public function content() {
        return $this->response->getContent();
    }

    public function getContentJson() {
        return json_decode($this->response->getContent(), true);
    }

    public function assertStatus(int $status): static
    {
        \PHPUnit\Framework\TestCase::assertSame($this->response->getStatusCode(), $status);
        return $this;
    }

    public function assertContent(string $content): static
    {
        \PHPUnit\Framework\TestCase::assertSame($this->response->getContent(), $content);
        return $this;
    }

    public function json(string $key=null)
    {
        if ($key) {
            return $this->getContentJson()[$key];
        } else {
            return $this->getContentJson();
        }
    }
}
