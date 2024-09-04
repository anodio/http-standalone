<?php

namespace Anodio\Http\Trap;

use Anodio\Core\ContainerStorage;
use Anodio\Http\Attributes\PostInterceptor;
use Anodio\Http\Middlewares\PostInterceptorInterface;
use DI\Attribute\Inject;
use olvlvl\ComposerAttributeCollector\Attributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
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
        if (!$exceptionEvent->hasResponse()) {
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
        $this->runPostInterceptors(
            $exceptionEvent->getRequest(),
            $exceptionEvent->getResponse(),
            $exceptionEvent->getRequest()->attributes->get('_controller')[0],
            $exceptionEvent->getRequest()->attributes->get('_controller')[1]
        );
    }

    private function runPostInterceptors(\Symfony\Component\HttpFoundation\Request $request, Response $response, object $controller, string $methodName)
    {
        $controllerName = get_class($controller);
        $targets = Attributes::forClass($controllerName);
        $postInterceptors = [];
        foreach ($targets->methodsAttributes as $methodNameFromTarget=>$methodTargets) {
            if ($methodNameFromTarget!==$methodName) {
                continue;
            }

            foreach ($methodTargets as $methodTargetAttribute) {
                if ($methodTargetAttribute instanceof PostInterceptor) {
                    $postInterceptors[] = $methodTargetAttribute;
                    continue;
                }
            }
        }
        usort($postInterceptors, function($a, $b) {
            return $a->priority<=>$b->priority;
        });

        if (count($postInterceptors)==0) {
            return;
        }
        $container = ContainerStorage::getContainer();
        foreach ($postInterceptors as $postInterceptor) {
            /** @var PostInterceptorInterface $interceptor */
            $interceptor = $container->get($postInterceptor->interceptor);
            if (!($interceptor instanceof PostInterceptorInterface)) {
                throw new \Exception('Post interceptor must implement PostInterceptorInterface');
            }
            $interceptor->intercept($request, $response);
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

        if (
            $exception instanceof ResourceNotFoundException
            || $exception instanceof NoConfigurationException
        ) {
            return new HttpException(404, $exception->getMessage(), $exception);
        }

        if ($exception instanceof MethodNotAllowedException) {
            return new HttpException(405, $exception->getMessage(), $exception);
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
