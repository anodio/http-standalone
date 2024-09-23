<?php

namespace Anodio\Http\ServiceProviders;

use Anodio\Core\Attributes\ServiceProvider;
use Symfony\Component\Routing\Attribute\Route;
use Anodio\Http\Listeners\ResponseConverter;
use Anodio\Http\Trap\HttpExceptionTrap;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetMethod;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

#[ServiceProvider(40)]
class HttpServerServiceProvider implements \Anodio\Core\AttributeInterfaces\ServiceProviderInterface
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {

        $containerBuilder->addDefinitions([
            EventDispatcher::class => \DI\create()
                ->method('addListener', KernelEvents::VIEW, [\Di\get(ResponseConverter::class), 'convert'])
                ->method('addListener', KernelEvents::REQUEST, [\Di\get(\Anodio\Http\Listeners\RequestEvent::class), 'requestGot'])
                ->method('addListener', KernelEvents::EXCEPTION, [\Di\get(HttpExceptionTrap::class), 'report']),
            EventDispatcherInterface::class => \DI\get(EventDispatcher::class),
            ControllerResolver::class=> \DI\create(),
            \Anodio\Http\Resolvers\ArgumentResolver::class=> \DI\create(),
            RequestStack::class=> \DI\create(),
            HttpKernel::class=> \DI\create()->constructor(
                \DI\get(EventDispatcher::class),
                \DI\get(ControllerResolver::class),
                \DI\get(RequestStack::class),
                \DI\get(\Anodio\Http\Resolvers\ArgumentResolver::class)
            ),
        ]);

        $targets = Attributes::findTargetMethods(Route::class);
        foreach ($targets as $target) {
            $containerBuilder->addDefinitions([
                $target->class=>\DI\autowire(),
            ]);

            $reflectionMethod = new \ReflectionMethod($target->class, $target->name);
            $parameters = $reflectionMethod->getParameters();
            $argumentsDict = [];
            foreach ($parameters as $parameter) {
                if (class_exists($parameter->getType()->getName())) {
                    $reflection =  new \ReflectionClass($parameter->getType()->getName());
                    if ($reflection->getParentClass()) {
                        $parentClass = $reflection->getParentClass()->getName();
                        if ($reflection->getParentClass()->getParentClass()) {
                            $parentParentClass = $reflection->getParentClass()->getParentClass()->getName();
                        }
                    } else {
                        $parentClass = null;
                    }
                    $argumentsDict[$parameter->getName()] = [
                        'class'=>$parameter->getType()->getName(),
                        'parentClass'=>$parentClass,
                        'parentParentClass'=>$parentParentClass??null,
                        'nullable'=>$parameter->getType()->allowsNull(),
                    ];
                } else {
                    $argumentsDict[$parameter->getName()] = [
                        'class'=>$parameter->getType()->getName(),
                        'nullable'=>$parameter->getType()->allowsNull(),
                    ];
                }
            }
            $containerBuilder->addDefinitions([
                '_arguments_'.$target->class.'::'.$target->name=>\Di\factory(function(array $params) {
                    return $params;
                })->parameter('params', $argumentsDict),
            ]);

        }

        $routeCollectionArray = [];
        $targets = Attributes::findTargetMethods(Route::class);
        /** @var TargetMethod $target */
        foreach ($targets as $target) {
            /** @var Route $routeAttribute */
            $routeAttribute = $target->attribute;
            $routeCollectionArray[$routeAttribute->getName()??$routeAttribute->getPath()] = [
                'path'=>$routeAttribute->getPath(),
                'defaults'=>array_merge($routeAttribute->getDefaults(), ['_controller'=>$target->class.'::'.$target->name]),
                'requirements'=>$routeAttribute->getRequirements(),
                'options'=>$routeAttribute->getOptions(),
                'host'=>$routeAttribute->getHost(),
                'schemes'=>$routeAttribute->getSchemes(),
                'methods'=>$routeAttribute->getMethods(),
                'condition'=>$routeAttribute->getCondition(),
            ];
        }

        $containerBuilder->addDefinitions([
            RouteCollection::class=>\DI\factory(function (array $routeCollectionArray) {
                $routeCollection = new RouteCollection();
                foreach ($routeCollectionArray as $name=>$routeInfo) {
                    $routeCollection->add(
                        $name,
                        new \Symfony\Component\Routing\Route(
                            path: $routeInfo['path'],
                            defaults: $routeInfo['defaults'],
                            requirements: $routeInfo['requirements'],
                            options: $routeInfo['options'],
                            host: $routeInfo['host'],
                            schemes: $routeInfo['schemes'],
                            methods: $routeInfo['methods'],
                            condition: $routeInfo['condition'],
                        )
                    );
                }
                return $routeCollection;
            })->parameter('routeCollectionArray', $routeCollectionArray),
        ]);
    }
}
