<?php

namespace Anodio\Http\Listeners;

use Anodio\Core\ContainerStorage;
use Anodio\Http\Attributes\PostMiddleware;
use Anodio\Http\Middlewares\PostInterceptorInterface;
//use Anodio\Templating\Support\Template;
use olvlvl\ComposerAttributeCollector\Attributes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class ResponseConverter
{
    public function convert(ViewEvent $viewEvent)
    {
        $response = new Response();
        $controllerResult = $viewEvent->getControllerResult();
        if (is_null($controllerResult)) {
            $response->headers->add(['Content-Type' => 'text/html']);
            $response->setContent('');
            $viewEvent->setResponse($response);
//        } elseif ($controllerResult instanceof Template) {
//            $response->headers->add(['Content-Type' => 'text/html']);
//            $response->setContent($controllerResult->getRenderedBody());
//            $viewEvent->setResponse($response);
        } elseif (is_string($controllerResult)) {
            $response->headers->add(['Content-Type' => 'text/html']);
            $response->setContent($controllerResult);
            $viewEvent->setResponse($response);
        } elseif(is_array($controllerResult)) {
            $response->headers->add(['Content-Type' => 'application/json']);
            $response->setContent(json_encode($controllerResult));
            $viewEvent->setResponse($response);
        } elseif($controllerResult instanceof Response) {
            $viewEvent->setResponse($controllerResult);
        } elseif ($controllerResult instanceof \JsonSerializable) {
            $response->headers->add(['Content-Type' => 'application/json']);
            $response->setContent(json_encode($controllerResult));
            $viewEvent->setResponse($response);
        } elseif (is_object($controllerResult)) {
            $response->headers->add(['Content-Type' => 'application/json']);
            $response->setContent(json_encode($controllerResult));
            $viewEvent->setResponse($response);
        } else {
            $response->headers->add(['Content-Type' => 'text/html']);
            $response->setContent('Invalid response from controller');
            $viewEvent->setResponse($response);
        }
        $request = $viewEvent->getRequest();
        $controller = $request->attributes->get('_controller')[0];
        $methodName = $request->attributes->get('_controller')[1];
        $this->runPostInterceptors($request, $response, $controller, $methodName);
    }

    private function runPostInterceptors(\Symfony\Component\HttpFoundation\Request $request, Response $response, object $conroller, string $methodName)
    {
        $controllerName = get_class($conroller);
        $targets = Attributes::forClass($controllerName);
        $postInterceptors = [];
        foreach ($targets->methodsAttributes as $methodNameFromTarget=>$methodTargets) {
            if ($methodNameFromTarget!==$methodName) {
                continue;
            }

            foreach ($methodTargets as $methodTargetAttribute) {
                if ($methodTargetAttribute instanceof PostMiddleware) {
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
}
