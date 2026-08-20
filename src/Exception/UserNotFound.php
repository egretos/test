<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Exception;

final class UserNotFound extends DummyJsonUsersException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('DummyJSON user with ID %d was not found.', $id));
    }
}
