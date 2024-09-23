<?php

namespace Anodio\Http\Attributes;

use JetBrains\PhpStorm\Deprecated;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
#[Deprecated(reason: 'Use Symfony\'s Route attribute instead')]
class Route extends \Symfony\Component\Routing\Attribute\Route
{

}
