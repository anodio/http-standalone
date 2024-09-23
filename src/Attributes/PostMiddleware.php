<?php

namespace Anodio\Http\Attributes;

use Anodio\Core\Abstraction\AbstractAttribute;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
class PostMiddleware extends AbstractAttribute
{
    public int $priority = 0;
    public string $interceptor;

    public function __construct(string $interceptor, int $priority = 0)
    {
        $this->interceptor = $interceptor;
        $this->priority = $priority;
    }
}
