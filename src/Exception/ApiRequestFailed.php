<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Exception;

use Throwable;

final class ApiRequestFailed extends DummyJsonUsersException
{
    public static function forStatusCode(int $statusCode): self
    {
        return new self(sprintf('DummyJSON API request failed with status code %d.', $statusCode));
    }

    public static function fromTransportException(Throwable $exception): self
    {
        return new self('DummyJSON API request failed before a complete response was received.', 0, $exception);
    }
}
