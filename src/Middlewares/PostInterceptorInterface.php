<?php

namespace Anodio\Http\Middlewares;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface PostInterceptorInterface
{
    public function intercept(Request $request, Response $response);
}