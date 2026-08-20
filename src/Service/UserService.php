<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Service;

use Challenge\DummyJsonUsers\Dto\PaginatedUsers;
use Challenge\DummyJsonUsers\Dto\User;
use Challenge\DummyJsonUsers\Exception\ApiRequestFailed;
use Challenge\DummyJsonUsers\Exception\InvalidApiResponse;
use Challenge\DummyJsonUsers\Exception\UserNotFound;
use JsonException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class UserService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl = 'https://dummyjson.com',
    ) {
    }

    public static function createHttpClient(?HttpClientInterface $httpClient = null): self
    {
        return new self($httpClient ?? HttpClient::create());
    }

    public function getUserById(int $id): User
    {
        if ($id < 1) {
            throw new UserNotFound('User ID must be greater than zero.');
        }

        $response = $this->request('GET', sprintf('/users/%d', $id));

        if ($response->getStatusCode() === 404) {
            throw UserNotFound::withId($id);
        }

        $this->throwIfResponseFailed($response);

        return $this->arrayToUserDTO($this->decode($response));
    }

    /**
     * Returns a paginated list of users.
     */
    public function listUsers(int $page = 1, int $perPage = 30): PaginatedUsers
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $skip = ($page - 1) * $perPage;

        $response = $this->request('GET', '/users', [
            'query' => [
                'limit' => $perPage,
                'skip' => $skip,
            ],
        ]);

        $this->throwIfResponseFailed($response);

        $data = $this->decode($response);

        if (!isset($data['users']) || !is_array($data['users'])) {
            throw InvalidApiResponse::missingField('users');
        }

        $users = array_map(
            fn (mixed $user): User => $this->arrayToUserDTO($this->assertArray($user)),
            array_values($data['users']),
        );

        return new PaginatedUsers(
            users: $users,
            total: $this->readInt($data, 'total'),
            limit: $this->readInt($data, 'limit'),
            skip: $this->readInt($data, 'skip'),
        );
    }

    public function addUser(string $firstName, string $lastName, string $email): int
    {
        $response = $this->request('POST', '/users/add', [
            'json' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
            ],
        ]);

        $this->throwIfResponseFailed($response);

        return $this->readInt($this->decode($response), 'id');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(string $method, string $path, array $options = []): ResponseInterface
    {
        try {
            return $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);
        } catch (TransportExceptionInterface $exception) {
            throw new ApiRequestFailed('DummyJSON API request failed before a response was received.', 0, $exception);
        }
    }

    private function throwIfResponseFailed(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiRequestFailed::forStatusCode($statusCode);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        try {
            $decoded = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidApiResponse::invalidJson();
        }

        return $this->assertArray($decoded);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function arrayToUserDTO(array $data): User
    {
        return new User(
            id: $this->readInt($data, 'id'),
            firstName: $this->readString($data, 'firstName'),
            lastName: $this->readString($data, 'lastName'),
            email: $this->readString($data, 'email'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assertArray(mixed $value): array
    {
        if (!is_array($value)) {
            throw InvalidApiResponse::invalidJson();
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readInt(array $data, string $field): int
    {
        if (!isset($data[$field]) || !is_int($data[$field])) {
            throw InvalidApiResponse::missingField($field);
        }

        return $data[$field];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readString(array $data, string $field): string
    {
        if (!isset($data[$field]) || !is_string($data[$field])) {
            throw InvalidApiResponse::missingField($field);
        }

        return $data[$field];
    }
}
