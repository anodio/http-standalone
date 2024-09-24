<?php

namespace Anodio\Http\Session;

use Anodio\Core\ContainerStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorageFactory;

class SessionFactory
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function createSession(): SessionInterface
    {
        $redisSessionHandler = ContainerStorage::getContainer()->get(RedisSessionHandler::class);
        $storageFactory = new PhpBridgeSessionStorageFactory($redisSessionHandler);
        $storage = $storageFactory->createStorage($this->request);
        $session = new Session($storage);
        $session->start();
        ContainerStorage::getContainer()->set(Session::class, $session);
        return $session;
    }
}
