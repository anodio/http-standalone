<?php

namespace Anodio\Http\Resolvers;

use Anodio\Core\ContainerStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

class ArgumentResolver implements ArgumentResolverInterface
{

    public function getArguments(Request $request, callable $controller, ?\ReflectionFunctionAbstract $reflector = null): array
    {
        $container = ContainerStorage::getContainer();
        $arguments = $container->get('_arguments_'.get_class($controller[0]).'::'.$controller[1]);
        $collectedArguments = [];
        foreach ($arguments as $name=>$argument) {
            if ($argument['class'] == Request::class) {
                $collectedArguments[] = $request;
                continue;
            }
            if ($argument['class']=='string') {
                $collectedArguments[] = $request->get($name);
                continue;
            }
            if ($argument['class']=='int') {
                $collectedArguments[] = (int)$request->get($name);
                continue;
            }
            if ($argument['class']=='float') {
                $collectedArguments[] = (float)$request->get($name);
                continue;
            }
            if ($argument['class']=='bool') {
                $collectedArguments[] = (bool)$request->get($name);
                continue;
            }
            if ($argument['class']=='array') {
                $collectedArguments[] = $request->get($name);
                continue;
            }
            if (class_exists(\Spatie\LaravelData\Data::class) && isset($argument['parentClass']) && $argument['parentClass']==\Spatie\LaravelData\Data::class) {
                if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
                    $data = $request->request->all();
                } else {
                    $data = $request->query->all();
                }
                $collectedArguments[] = ($argument['class'])::from($data);
                continue;
            }
        }

        return $collectedArguments;
    }
}