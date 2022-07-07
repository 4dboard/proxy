<?php declare(strict_types=1);

namespace Pdp;

use InvalidArgumentException;

final class syntaxError extends InvalidArgumentException implements cannotProcessHost
{
    private ?idnaInfo $idnaInfo;

    private function __construct(string $message, idnaInfo $idnaInfo = null)
    {
        parent::__construct($message);
        $this->idnaInfo = $idnaInfo;
    }

    public static function dueToInvalidCharacters(string $domain): self
    {
        return new self('The host `' . $domain . '` is invalid: it contains invalid characters.');
    }

    public static function dueToMalformedValue(string $domain): self
    {
        return new self('The host `' . $domain . '` is malformed; Verify its length and/or characters.');
    }

    public static function dueToIDNAError(string $domain, idnaInfo $idnaInfo): self
    {
        return new self('The host `' . $domain . '` is invalid for IDN conversion.', $idnaInfo);
    }

    public static function dueToInvalidSuffix(host $publicSuffix, string $type = ''): self
    {
        if ('' === $type) {
            return new self('The suffix `"' . ($publicSuffix->value() ?? 'NULL') . '"` is invalid.');
        }
        return new self('The suffix `"' . ($publicSuffix->value() ?? 'NULL') . '"` is an invalid `' . $type . '` suffix.');
    }

    public static function dueToUnsupportedType(string $domain): self
    {
        return new self('The domain `' . $domain . '` is invalid: this is an IPv4 host.');
    }

    public static function dueToInvalidLabelKey(host $domain, int $key): self
    {
        return new self('the given key `' . $key . '` is invalid for the domain `' . ($domain->value() ?? 'NULL') . '`.');
    }

    public function idnaInfo(): ?idnaInfo
    {
        return $this->idnaInfo;
    }
}