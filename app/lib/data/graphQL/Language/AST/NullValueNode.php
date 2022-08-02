<?php

declare(strict_types=1);

namespace yxorP\app\lib\graphQL\Language\AST;

class NullValueNode extends Node implements ValueNodeInterface
{
    /** @var string */
    public $kind = NodeKind::NULL;
}
