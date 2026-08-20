<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Exception;

final class InvalidApiResponse extends DummyJsonUsersException
{
    public static function missingField(string $field): self
    {
        return new self(sprintf('DummyJSON API response is missing required field "%s".', $field));
    }

    public static function invalidJson(): self
    {
        return new self('DummyJSON API response could not be decoded as JSON.');
    }
}
