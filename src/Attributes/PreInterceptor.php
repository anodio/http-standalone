<?php

namespace Anodio\Http\Attributes;

use Anodio\Core\Abstraction\AbstractAttribute;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
class PreInterceptor extends AbstractAttribute
{
    public string $interceptor;
        public int    $priority; //who has higher - runs first)
    public function __construct(string $interceptor, int $priority)
    {
        $this->interceptor = $interceptor;
        $this->priority = $priority;
    }
}
