<?php

namespace Anodio\Http\Session;

use Anodio\Core\ContainerStorage;
use Symfony\Component\HttpFoundation\Request;

class Session
{
    public static function getId(): ?string {
        return ContainerStorage::getContainer()->get(Request::class)->getSession()->getId();
    }

    public static function setFlash(string $key, string $value) {
        $session = ContainerStorage::getContainer()->get(Request::class)->getSession();
        $session->getFlashBag()->add($key, $value);
        $session->save();
    }

    public static function getFlash(string $key): ?string {
        return ContainerStorage::getContainer()->get(Request::class)->getSession()->getFlashBag()->get($key);
    }

    public static function set(string $key, $value) {
        $session = ContainerStorage::getContainer()->get(Request::class)->getSession();
        $session->set($key, $value);
        $session->save();
    }

    public static function get(string $key) {
        return ContainerStorage::getContainer()->get(Request::class)->getSession()->get($key);
    }
}
