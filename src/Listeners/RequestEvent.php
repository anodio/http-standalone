<?php

namespace Anodio\Http\Listeners;

use Anodio\Core\ContainerStorage;
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
        $event->getRequest()->attributes->add($attributes);
    }
}