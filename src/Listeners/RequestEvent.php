<?php

namespace Anodio\Http\Listeners;

use Anodio\Core\ContainerStorage;
use Anodio\Http\Attributes\PreInterceptor;
use Anodio\Http\Middlewares\PreInterceptorInterface;
use olvlvl\ComposerAttributeCollector\Attributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class RequestEvent
{
    public function requestGot(\Symfony\Component\HttpKernel\Event\RequestEvent $event) {
        $container = ContainerStorage::getContainer();

        $context = new RequestContext();
        $context->fromRequest($event->getRequest());

        $matcher = new UrlMatcher($container->get(RouteCollection::class), $context);
        $attributes = $matcher->match($event->getRequest()->getPathInfo());


        $controllerExploded = explode('::', $attributes['_controller']);
        $controllerName = $controllerExploded[0];
        $methodName = $controllerExploded[1];

        $attributes['_controller'] = [$container->get($controllerName), $methodName];

        $event->getRequest()->attributes->add($attributes);

        $this->runPreInterceptors($event->getRequest(), $controllerName, $methodName);

    }

    private function runPreInterceptors(Request $request, string $controllerName, string $methodName) {
        $targets = Attributes::forClass($controllerName);
        $preInterceptors = [];
        foreach ($targets->methodsAttributes as $methodNameFromTarget=>$methodTargets) {
            if ($methodNameFromTarget!==$methodName) {
                continue;
            }

            foreach ($methodTargets as $methodTargetAttribute) {
                if ($methodTargetAttribute instanceof PreInterceptor) {
                    $preInterceptors[] = $methodTargetAttribute;
                    continue;
                }
            }
        }
        usort($preInterceptors, function($a, $b) {
            return $a->priority<=>$b->priority;
        });

        if (count($preInterceptors)==0) {
            return;
        }
        $container = ContainerStorage::getContainer();
        foreach ($preInterceptors as $preInterceptor) {
            /** @var PreInterceptorInterface $interceptor */
            $interceptor = $container->get($preInterceptor->interceptor);
            if (!($interceptor instanceof PreInterceptorInterface)) {
                throw new \Exception('Interceptor must implement PreInterceptorInterface');
            }
            $interceptor->intercept($request);
        }
    }
}