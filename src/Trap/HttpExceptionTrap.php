<?php

namespace Anodio\Http\Trap;

use Anodio\Core\AttributeInterfaces\AbstractExceptionTrap;
use Anodio\Core\Attributes\ExceptionTrap;
use DI\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

#[ExceptionTrap(loggerName: 'logger.http')]
class HttpExceptionTrap extends AbstractExceptionTrap
{
    private const SKIP_CODES = [404, 400, 401, 403];

    private readonly Response $response;
    public function report(\Throwable $exception): void
    {
        $convertedException = $this->convertExceptionToHttpException($exception);
        $this->createResponse($convertedException);
        if (method_exists($convertedException, 'getStatusCode')) {
            if (!in_array($convertedException->getStatusCode(), self::SKIP_CODES)) {
                $this->logger->error($convertedException->getMessage(), ['exception' => $convertedException, 'originalException'=>$exception]);
            }
        } else {
            if (!in_array($convertedException->getCode(), self::SKIP_CODES)) {
                $this->logger->error($convertedException->getMessage(), ['exception' => $convertedException, 'originalException'=>$exception]);
            }
        }
    }

    public function getResponse(array $headers = []): Response{
        $this->response->headers->add($headers);
        return $this->response;
    }

    private function createResponse(\Throwable $exception)
    {
        if (method_exists($exception, 'getStatusCode')) {
            $this->response = new Response($exception->getMessage(), $exception->getStatusCode());
        } else {
            $this->response = new Response($exception->getMessage(), $exception->getCode()?:500);
        }
    }

    private function convertExceptionToHttpException(\Throwable $exception): \Throwable
    {
        if($exception instanceof ResourceNotFoundException) {
            return new HttpException(404, $exception->getMessage(), $exception);
        }
        if ($exception instanceof HttpException) {
            return $exception;
        }
        if ($exception->getCode() === 0) {
            $exception->code = 500;
        }
        return $exception;
    }
}