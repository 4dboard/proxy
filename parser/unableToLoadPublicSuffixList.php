<?php declare(strict_types=1);

namespace Pdp;

use InvalidArgumentException;
use Throwable;

final class unableToLoadPublicSuffixList extends InvalidArgumentException implements cannotProcessHost
{
    public static function dueToInvalidRule(?string $line, Throwable $exception): self
    {
        return new self('The following rule "' . ($line ?? 'NULL') . '" could not be processed because it is invalid.', 0, $exception);
    }
}