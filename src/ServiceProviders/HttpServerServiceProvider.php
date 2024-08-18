<?php

namespace Anodio\Http\ServiceProviders;

use Anodio\Core\Attributes\ServiceProvider;
use Anodio\Http\Logger\LoggerFactory;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetMethod;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouteCollection;

#[ServiceProvider]
class HttpServerServiceProvider implements \Anodio\Core\AttributeInterfaces\ServiceProviderInterface
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            'logger.http'=>\DI\factory([LoggerFactory::class, 'createLogger']),
        ]);

        $containerBuilder->addDefinitions([
            \Anodio\Http\Server\HttpServer::class => \DI\autowire(),
        ]);

        $containerBuilder->addDefinitions([
            EventDispatcher::class => \DI\create(),
            ControllerResolver::class=> \DI\create(),
            ArgumentResolver::class=> \DI\create(),
            RequestStack::class=> \DI\create(),
            HttpKernel::class=> \DI\create()->constructor(
                \DI\get(EventDispatcher::class),
                \DI\get(ControllerResolver::class),
                \DI\get(RequestStack::class),
                \DI\get(ArgumentResolver::class)
            ),
        ]);


        $containerBuilder->addDefinitions([
            RouteCollection::class=>\DI\factory(function () {
              $targets = Attributes::findTargetMethods(\Symfony\Component\Routing\Attribute\Route::class);
                $routeCollection = new RouteCollection();
                /** @var TargetMethod $target */
                foreach ($targets as $target) {
                    /** @var Route $routeAttribute */
                    $routeAttribute = $target->attribute;
                    $routeCollection->add(
                        $routeAttribute->getName()??$routeAttribute->getPath(),
                        new \Symfony\Component\Routing\Route(
                            path: $routeAttribute->getPath(),
                            defaults: array_merge($routeAttribute->getDefaults(), ['_controller'=>$target->class.'::'.$target->name]),
                            requirements: $routeAttribute->getRequirements(),
                            options: $routeAttribute->getOptions(),
                            host: $routeAttribute->getHost(),
                            schemes: $routeAttribute->getSchemes(),
                            methods: $routeAttribute->getMethods(),
                            condition: $routeAttribute->getCondition(),
                        )
                    );
                }
                return $routeCollection;
            }),
        ]);
    }
}