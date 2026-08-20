<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Exception;

final class ApiRequestFailed extends DummyJsonUsersException
{
    public static function forStatusCode(int $statusCode): self
    {
        return new self(sprintf('DummyJSON API request failed with status code %d.', $statusCode));
    }
}
