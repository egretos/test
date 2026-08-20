<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Tests\Service;

use Challenge\DummyJsonUsers\Dto\PaginatedUsers;
use Challenge\DummyJsonUsers\Dto\User;
use Challenge\DummyJsonUsers\Exception\ApiRequestFailed;
use Challenge\DummyJsonUsers\Exception\InvalidApiResponse;
use Challenge\DummyJsonUsers\Exception\UserNotFound;
use Challenge\DummyJsonUsers\Service\UserService;
use Challenge\DummyJsonUsers\Tests\Support\HttpRequestRecorder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

final class UserServiceTest extends TestCase
{
    public function testItRetrievesSingleUserById(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse(json_encode([
                'id' => 1,
                'firstName' => 'Emily',
                'lastName' => 'Johnson',
                'email' => 'emily.johnson@example.com',
                'ignored' => 'value',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $user = $service->getUserById(1);

        $http->assertRequest('GET', 'https://dummyjson.com/users/1');

        self::assertInstanceOf(User::class, $user);
        self::assertSame(1, $user->id);
        self::assertSame([
            'id' => 1,
            'firstName' => 'Emily',
            'lastName' => 'Johnson',
            'email' => 'emily.johnson@example.com',
        ], $user->toArray());
    }

    public function testItUsesCustomBaseUrl(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse(json_encode([
                'id' => 1,
                'firstName' => 'Emily',
                'lastName' => 'Johnson',
                'email' => 'emily.johnson@example.com',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new UserService($http->client(), 'https://example.test/api/');

        $http->assertNoRequests();

        $service->getUserById(1);

        $http->assertRequest('GET', 'https://example.test/api/users/1');
    }

    public function testItRejectsInvalidPage(): void
    {
        $http = new HttpRequestRecorder();
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $this->expectException(InvalidArgumentException::class);

        $service->listUsers(page: 0);
    }

    public function testItRejectsInvalidPerPage(): void
    {
        $http = new HttpRequestRecorder();
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $this->expectException(InvalidArgumentException::class);

        $service->listUsers(perPage: 0);
    }

    public function testItRetrievesPaginatedUsers(): void
    {
        $http = new HttpRequestRecorder([
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
                'skip' => 20,
                'limit' => 10,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $users = $service->listUsers(page: 3, perPage: 10);

        $http->assertRequest('GET', 'https://dummyjson.com/users?limit=10&skip=20');

        self::assertInstanceOf(PaginatedUsers::class, $users);
        self::assertSame([
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
            'limit' => 10,
            'skip' => 20,
        ], $users->toArray());
    }

    public function testItAddsUserAndReturnsId(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse(json_encode([
                'id' => 209,
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'email' => 'ada@example.com',
            ], JSON_THROW_ON_ERROR), ['http_code' => 201]),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        self::assertSame(209, $service->addUser('Ada', 'Lovelace', 'ada@example.com'));

        $http->assertRequest(
            'POST',
            'https://dummyjson.com/users/add',
            '{"firstName":"Ada","lastName":"Lovelace","email":"ada@example.com"}',
        );
    }

    public function testItAllowsCustomRequests(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse(json_encode([
                'carts' => [
                    [
                        'id' => 1,
                    ],
                ],
                'total' => 1,
                'skip' => 0,
                'limit' => 1,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $response = $service->request('GET', '/carts', [
            'query' => [
                'limit' => 1,
            ],
        ]);

        $http->assertRequest('GET', 'https://dummyjson.com/carts?limit=1');
        self::assertSame([
            'carts' => [
                [
                    'id' => 1,
                ],
            ],
            'total' => 1,
            'skip' => 0,
            'limit' => 1,
        ], $response);
    }

    public function testCustomRequestsCanSendJsonBodies(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse(json_encode([
                'ok' => true,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $response = $service->request('POST', '/custom', [
            'json' => [
                'name' => 'Ada',
            ],
        ]);

        $http->assertRequest('POST', 'https://dummyjson.com/custom', '{"name":"Ada"}');
        self::assertSame(['ok' => true], $response);
    }

    public function testCustomRequestsUseDomainApiFailures(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse('{"message":"Too many requests"}', [
                'http_code' => 429,
                'response_headers' => [
                    'Retry-After' => '60',
                ],
            ]),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->request('GET', '/anything');
            self::fail('Expected ApiRequestFailed to be thrown.');
        } catch (ApiRequestFailed $exception) {
            $http->assertRequest('GET', 'https://dummyjson.com/anything');
            self::assertSame(429, $exception->getStatusCode());
            self::assertSame(429, $exception->getCode());
            self::assertSame('{"message":"Too many requests"}', $exception->getResponseBody());
            self::assertSame('60', $exception->getRetryAfter());
            self::assertStringContainsString('Retry-After: 60', $exception->getMessage());
        }
    }

    /**
     * @param non-empty-string $firstName
     * @param non-empty-string $lastName
     * @param non-empty-string $email
     */
    #[DataProvider('invalidNewUserData')]
    public function testItRejectsInvalidNewUserData(string $firstName, string $lastName, string $email): void
    {
        $http = new HttpRequestRecorder();
        $service = new UserService($http->client());

        $http->assertNoRequests();

        $this->expectException(InvalidArgumentException::class);

        $service->addUser($firstName, $lastName, $email);
    }

    /**
     * @return array<string, array{firstName: string, lastName: string, email: string}>
     */
    public static function invalidNewUserData(): array
    {
        return [
            'empty first name' => [
                'firstName' => '',
                'lastName' => 'Lovelace',
                'email' => 'ada@example.com',
            ],
            'empty last name' => [
                'firstName' => 'Ada',
                'lastName' => '',
                'email' => 'ada@example.com',
            ],
            'invalid email' => [
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'email' => 'not-an-email',
            ],
        ];
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
        $http = new HttpRequestRecorder([
            new MockResponse('{"message":"User not found"}', ['http_code' => 404]),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->getUserById(999);
            self::fail('Expected UserNotFound to be thrown.');
        } catch (UserNotFound) {
            $http->assertRequest('GET', 'https://dummyjson.com/users/999');
        }
    }

    public function testItThrowsApiRequestFailedForServerErrors(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse('{"message":"Internal error"}', ['http_code' => 500]),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->listUsers();
            self::fail('Expected ApiRequestFailed to be thrown.');
        } catch (ApiRequestFailed $exception) {
            $http->assertRequest('GET', 'https://dummyjson.com/users?limit=30&skip=0');
            self::assertSame(500, $exception->getStatusCode());
            self::assertSame(500, $exception->getCode());
            self::assertSame('{"message":"Internal error"}', $exception->getResponseBody());
            self::assertNull($exception->getRetryAfter());
        }
    }

    public function testItThrowsApiRequestFailedForTransportErrors(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse('', ['error' => 'Network failure']),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->getUserById(1);
            self::fail('Expected ApiRequestFailed to be thrown.');
        } catch (ApiRequestFailed $exception) {
            $http->assertRequest('GET', 'https://dummyjson.com/users/1');
            self::assertNull($exception->getStatusCode());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getResponseBody());
            self::assertNull($exception->getRetryAfter());
        }
    }

    public function testItThrowsInvalidApiResponseForMissingUserFields(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse('{"id":1,"firstName":"Emily"}'),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->getUserById(1);
            self::fail('Expected InvalidApiResponse to be thrown.');
        } catch (InvalidApiResponse) {
            $http->assertRequest('GET', 'https://dummyjson.com/users/1');
        }
    }

    public function testItThrowsInvalidApiResponseForInvalidJson(): void
    {
        $http = new HttpRequestRecorder([
            new MockResponse('not json'),
        ]);
        $service = new UserService($http->client());

        $http->assertNoRequests();

        try {
            $service->getUserById(1);
            self::fail('Expected InvalidApiResponse to be thrown.');
        } catch (InvalidApiResponse) {
            $http->assertRequest('GET', 'https://dummyjson.com/users/1');
        }
    }
}
