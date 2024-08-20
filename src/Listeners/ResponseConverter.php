<?php

namespace Anodio\Http\Listeners;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class ResponseConverter
{
    public function convert(ViewEvent $viewEvent)
    {
        $response = new Response();
        $controllerResult = $viewEvent->getControllerResult();
        if (is_string($controllerResult)) {
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
            $response->setContent($controllerResult->jsonSerialize());
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
    }
}