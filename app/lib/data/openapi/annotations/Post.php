<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace yxorP\app\lib\openapi\annotations;

/**
 * @Annotation
 */
class Post extends Operation
{
    /**
     * @inheritdoc
     */
    public static $_parents = [
        PathItem::class,
    ];
    /**
     * @inheritdoc
     */
    public $method = 'post';
}
