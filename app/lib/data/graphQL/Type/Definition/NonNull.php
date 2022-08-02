<?php

declare(strict_types=1);

namespace yxorP\app\lib\graphQL\Type\Definition;

use yxorP\app\lib\graphQL\Type\Schema;

class NonNull extends Type implements WrappingType, OutputType, InputType
{
    /** @var callable():(NullableType&Type)|(NullableType&Type) */
    private $ofType;

    /**
     * code sniffer doesn't understand this syntax. Pr with a fix here: waiting on https://github.com/squizlabs/PHP_CodeSniffer/pull/2919
     * phpcs:disable Squiz.Commenting.FunctionComment.SpacingAfterParamType
     * @param callable():(NullableType&Type)|(NullableType&Type) $type
     */
    public function __construct($type)
    {
        $this->ofType = $type;
    }

    public function toString(): string
    {
        return $this->getWrappedType()->toString() . '!';
    }

    /**
     * @return (NullableType&Type)
     */
    public function getWrappedType(bool $recurse = false): Type
    {
        $type = $this->getOfType();

        return $recurse && $type instanceof WrappingType
            ? $type->getWrappedType($recurse)
            : $type;
    }

    public function getOfType()
    {
        return Schema::resolveType($this->ofType);
    }
}
