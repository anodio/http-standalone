<?php

namespace Bicycle\Http\Trap;

use Bicycle\Core\AttributeInterfaces\AbstractExceptionTrap;
use Bicycle\Core\Attributes\ExceptionTrap;
use DI\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

#[ExceptionTrap(loggerName: 'logger.http')]
class HttpExceptionTrap extends AbstractExceptionTrap
{
    private const SKIP_CODES = [404, 400, 401, 403];

    private readonly Response $response;
    public function report(\Throwable $exception): void
    {
        $this->createResponse($exception);
        if (!in_array($exception->getCode(), self::SKIP_CODES)) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function getResponse(array $headers = []): Response{
        $this->response->headers->add($headers);
        return $this->response;
    }

    private function createResponse(\Throwable $exception)
    {
        $this->response = new Response($exception->getMessage(), $exception->getCode()?:500);
    }
}