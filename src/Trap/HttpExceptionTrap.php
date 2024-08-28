<?php

namespace Anodio\Http\Trap;

use Anodio\Core\ContainerStorage;
use DI\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class HttpExceptionTrap
{

    private const SKIP_CODES = [404, 405, 422, 403, 401, 400];

    #[Inject('logger')]
    public LoggerInterface $logger;

    public function report(ExceptionEvent $exceptionEvent): void
    {
        $eventDispatcher = ContainerStorage::getContainer()->get(EventDispatcher::class);
        $eventDispatcher->dispatch($exceptionEvent, KernelEvents::EXCEPTION.'-CUSTOM');
        $exception = $exceptionEvent->getThrowable();
        $convertedException = $this->convertExceptionToHttpException($exception);
        if ($exceptionEvent->hasResponse()) {
            return;
        } else {
            $response = $this->createResponse($convertedException);
            $exceptionEvent->setResponse($response);
            if (method_exists($convertedException, 'getStatusCode')) {
                if (!$exceptionEvent->getKernel()) {
                    $this->logger->error($convertedException->getMessage(), ['exception' => $convertedException, 'originalException' => $exception]);
                }
            } else {
                if (!in_array($convertedException->getCode(), self::SKIP_CODES)) {
                    $this->logger->error($convertedException->getMessage(), ['exception' => $convertedException, 'originalException' => $exception]);
                }
            }
        }
    }

    private function createResponse(\Throwable $exception): Response
    {
        if (method_exists($exception, 'getStatusCode')) {
            return new Response($exception->getMessage(), $exception->getStatusCode());
        } else {
            return new Response($exception->getMessage(), $exception->getCode() ?: 500);
        }
    }

    private function convertExceptionToHttpException(\Throwable $exception): \Throwable
    {
        if ($exception instanceof \InvalidArgumentException) {
            return new HttpException(422, $exception->getMessage(), $exception);
        }

        if ($exception instanceof ResourceNotFoundException) {
            return new HttpException(404, $exception->getMessage(), $exception);
        }
        if ($exception instanceof HttpException) {
            return $exception;
        }
        if ($exception->getCode() === 0) {
            return new HttpException(
                statusCode: 500,
                message: $exception->getMessage(),
                previous: $exception,
                headers: [],
                code: 500,
            );
        }
        return $exception;
    }
}
