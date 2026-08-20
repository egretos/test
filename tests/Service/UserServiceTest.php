<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Tests\Service;

use Challenge\DummyJsonUsers\Dto\PaginatedUsers;
use Challenge\DummyJsonUsers\Dto\User;
use Challenge\DummyJsonUsers\Exception\ApiRequestFailed;
use Challenge\DummyJsonUsers\Exception\InvalidApiResponse;
use Challenge\DummyJsonUsers\Exception\UserNotFound;
use Challenge\DummyJsonUsers\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class UserServiceTest extends TestCase
{
    public function testItRetrievesSingleUserById(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse(json_encode([
                'id' => 1,
                'firstName' => 'Emily',
                'lastName' => 'Johnson',
                'email' => 'emily.johnson@example.com',
                'ignored' => 'value',
            ], JSON_THROW_ON_ERROR)),
        ]));

        $user = $service->getUserById(1);

        self::assertInstanceOf(User::class, $user);
        self::assertSame(1, $user->id);
        self::assertSame([
            'id' => 1,
            'firstName' => 'Emily',
            'lastName' => 'Johnson',
            'email' => 'emily.johnson@example.com',
        ], $user->toArray());
    }

    public function testItRetrievesPaginatedUsers(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse(json_encode([
                'users' => [
                    [
                        'id' => 1,
                        'firstName' => 'Emily',
                        'lastName' => 'Johnson',
                        'email' => 'emily.johnson@example.com',
                    ],
                    [
                        'id' => 2,
                        'firstName' => 'Michael',
                        'lastName' => 'Williams',
                        'email' => 'michael.williams@example.com',
                    ],
                ],
                'total' => 208,
                'skip' => 10,
                'limit' => 10,
            ], JSON_THROW_ON_ERROR)),
        ]));

        $users = $service->listUsers(page: 2, perPage: 10);

        self::assertInstanceOf(PaginatedUsers::class, $users);
        self::assertCount(2, $users->users);
        self::assertSame(208, $users->total);
        self::assertSame(10, $users->skip);
        self::assertSame(10, $users->limit);
        self::assertSame('Michael', $users->users[1]->firstName);
    }

    public function testItAddsUserAndReturnsId(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse(json_encode([
                'id' => 209,
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'email' => 'ada@example.com',
            ], JSON_THROW_ON_ERROR), ['http_code' => 201]),
        ]));

        self::assertSame(209, $service->addUser('Ada', 'Lovelace', 'ada@example.com'));
    }

    public function testUserCanBeSerializedToJson(): void
    {
        $user = new User(1, 'Emily', 'Johnson', 'emily.johnson@example.com');

        self::assertJsonStringEqualsJsonString(
            '{"id":1,"firstName":"Emily","lastName":"Johnson","email":"emily.johnson@example.com"}',
            json_encode($user, JSON_THROW_ON_ERROR),
        );
    }

    public function testItThrowsUserNotFoundFor404(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse('{"message":"User not found"}', ['http_code' => 404]),
        ]));

        $this->expectException(UserNotFound::class);

        $service->getUserById(999);
    }

    public function testItThrowsApiRequestFailedForServerErrors(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse('{"message":"Internal error"}', ['http_code' => 500]),
        ]));

        $this->expectException(ApiRequestFailed::class);

        $service->listUsers();
    }

    public function testItThrowsInvalidApiResponseForMissingUserFields(): void
    {
        $service = new UserService(new MockHttpClient([
            new MockResponse('{"id":1,"firstName":"Emily"}'),
        ]));

        $this->expectException(InvalidApiResponse::class);

        $service->getUserById(1);
    }
}
