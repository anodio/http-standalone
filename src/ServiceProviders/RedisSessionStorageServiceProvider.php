<?php

namespace Anodio\Http\ServiceProviders;

use Anodio\Core\AttributeInterfaces\ServiceProviderInterface;
use Anodio\Core\Attributes\ServiceProvider;
use Anodio\Http\Config\HttpConfig;
use Anodio\Http\Session\RedisSessionHandler;
use DI\ContainerBuilder;
use Exception;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\SessionHandlerFactory;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorageFactory;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;
use function DI\factory;

//#[ServiceProvider]
class RedisSessionStorageServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            RedisSessionHandler::class => factory(function(HttpConfig $config) {
                if (!extension_loaded('redis')) {
                    throw new Exception('Redis extension is not loaded');
                }
                $connection = RedisAdapter::createConnection($config->redisConnectionStringForSessionStore);
                return SessionHandlerFactory::createHandler($connection);
            }),
            Session::class => \DI\factory(function(Request $request, RedisSessionHandler $redisSessionHandler) {
                $sessionProxy = new SessionHandlerProxy($redisSessionHandler);
//                $storageFactory = new PhpBridgeSessionStorageFactory($sessionProxy);
//                $requestStack = new RequestStack();
//                $requestStack->push($request);
//                $sessionFactory = new SessionFactory($requestStack, $storageFactory);
//                $session = $sessionFactory->createSession();
//                $session->start();
//                return $session;
            })->parameter('request', \DI\get(Request::class))
            ->parameter('redisSessionHandler', \DI\get(RedisSessionHandler::class))
        ]);
    }
}
