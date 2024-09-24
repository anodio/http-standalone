<?php

namespace Anodio\Http\Session;

use Anodio\Core\ContainerStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class RedisSessionStorage implements SessionStorageInterface
{

    public function __construct(\SessionHandlerInterface $handler = null)
    {
    }

    public function start(): bool
    {
        $sessionId = ContainerStorage::getContainer()->get(Request::class)->cookies->get('PHPSESSID');
        if ($sessionId===null) {
            $sessionId = bin2hex(random_bytes(26));
        }
        ContainerStorage::getContainer()->get(Request::class)->cookies->set('PHPSESSID', $sessionId);
        return true;
    }

    public function isStarted(): bool
    {
        // TODO: Implement isStarted() method.
    }

    public function getId(): string
    {
        // TODO: Implement getId() method.
    }

    public function setId(string $id): void
    {
        // TODO: Implement setId() method.
    }

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    public function setName(string $name): void
    {
        // TODO: Implement setName() method.
    }

    public function regenerate(bool $destroy = false, ?int $lifetime = null): bool
    {
        // TODO: Implement regenerate() method.
    }

    public function save(): void
    {
        // TODO: Implement save() method.
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }

    public function getBag(string $name): SessionBagInterface
    {
        // TODO: Implement getBag() method.
    }

    public function registerBag(SessionBagInterface $bag): void
    {
        // TODO: Implement registerBag() method.
    }

    public function getMetadataBag(): MetadataBag
    {
        // TODO: Implement getMetadataBag() method.
    }
}
