<?php

namespace Anodio\Http\ServiceProviders;

use Anodio\Core\AttributeInterfaces\ServiceProviderInterface;
use Anodio\Core\Attributes\ServiceProvider;
use olvlvl\ComposerAttributeCollector\Attributes;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[ServiceProvider(priority: 30)]
class EventListenersServiceProvider implements ServiceProviderInterface
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $targets = Attributes::findTargetMethods(AsEventListener::class);
        $additionalEventsRegistration = [];
        foreach ($targets as $target) {
            if (!($target->attribute instanceof AsEventListener)) {
                continue;
            }
            $reflectionMethod = new \ReflectionMethod($target->class, $target->name);
            if (!$reflectionMethod->isStatic()) {
                throw new \Exception('Event listener '.$target->class.'::'.$target->name.' must be static');
            }
            $additionalEventsRegistration[] = [
                'eventName'=>$target->attribute->event,
                'className'=>$target->class,
                'methodName'=>$target->name
            ];
        }
        $containerBuilder->addDefinitions([
           'additionalEventsRegistration'=>$additionalEventsRegistration
        ]);
        $containerBuilder->addDefinitions([
            EventDispatcher::class=>\DI\decorate(function(EventDispatcher $previous, ContainerInterface $c) {
                foreach ($c->get('additionalEventsRegistration') as $key=>$value) {
                    $previous->addListener($value['eventName'], [$value['className'], $value['methodName']]);
                }
                return $previous;
            })
        ]);
    }
}
