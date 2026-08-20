<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Dto;

use JsonSerializable;

/**
 * @phpstan-type UserArray array{id: int, firstName: string, lastName: string, email: string}
 * @phpstan-type PaginatedUsersArray array{users: list<UserArray>, total: int, limit: int, skip: int}
 */
final readonly class PaginatedUsers implements JsonSerializable
{
    /**
     * @param list<User> $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $limit,
        public int $skip,
    ) {
    }

    /**
     * @return PaginatedUsersArray
     */
    public function toArray(): array
    {
        return [
            'users' => array_map(
                static fn (User $user): array => $user->toArray(),
                $this->users,
            ),
            'total' => $this->total,
            'limit' => $this->limit,
            'skip' => $this->skip,
        ];
    }

    /**
     * @return PaginatedUsersArray
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
