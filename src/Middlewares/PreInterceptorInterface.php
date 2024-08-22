<?php

namespace Anodio\Http\Middlewares;

use Symfony\Component\HttpFoundation\Request;

interface PreInterceptorInterface
{
    public function intercept(Request $request);
}