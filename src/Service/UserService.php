<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Service;

use Challenge\DummyJsonUsers\Dto\PaginatedUsers;
use Challenge\DummyJsonUsers\Dto\User;
use Challenge\DummyJsonUsers\Exception\ApiRequestFailed;
use Challenge\DummyJsonUsers\Exception\InvalidApiResponse;
use Challenge\DummyJsonUsers\Exception\UserNotFound;
use InvalidArgumentException;
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

    public static function createDefault(): self
    {
        return new self(HttpClient::create());
    }

    public function getUserById(int $id): User
    {
        if ($id < 1) {
            throw new UserNotFound('User ID must be greater than zero.');
        }

        $response = $this->sendRequest('GET', sprintf('/users/%d', $id));

        if ($this->responseStatusCode($response) === 404) {
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
        $this->ensurePositiveInteger($page, 'page');
        $this->ensurePositiveInteger($perPage, 'perPage');

        $skip = ($page - 1) * $perPage;

        $response = $this->sendRequest('GET', '/users', [
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
            fn (mixed $user): User => $this->arrayToUserDTO($this->ensureArrayResponseData($user)),
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
        $firstName = $this->ensureNonEmptyString($firstName, 'firstName');
        $lastName = $this->ensureNonEmptyString($lastName, 'lastName');
        $email = $this->ensureValidEmail($email);

        $response = $this->sendRequest('POST', '/users/add', [
            'json' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
            ],
        ]);

        $this->throwIfResponseFailed($response);

        return $this->readInt($this->decode($response), 'id');
    }

    private function ensurePositiveInteger(int $value, string $field): void
    {
        if ($value < 1) {
            throw new InvalidArgumentException(sprintf('%s must be greater than zero.', $field));
        }
    }

    private function ensureNonEmptyString(string $value, string $field): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(sprintf('%s must not be empty.', $field));
        }

        return $value;
    }

    private function ensureValidEmail(string $email): string
    {
        $email = $this->ensureNonEmptyString($email, 'email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('email must be a valid email address.');
        }

        return $email;
    }

    /**
     * Makes a custom request to the configured DummyJSON resource and returns decoded response data.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $response = $this->sendRequest($method, $path, $options);

        $this->throwIfResponseFailed($response);

        return $this->decode($response);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function sendRequest(string $method, string $path, array $options = []): ResponseInterface
    {
        try {
            return $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);
        } catch (TransportExceptionInterface $exception) {
            throw ApiRequestFailed::fromTransportException($exception);
        }
    }

    private function throwIfResponseFailed(ResponseInterface $response): void
    {
        $statusCode = $this->responseStatusCode($response);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiRequestFailed::forStatusCode(
                statusCode: $statusCode,
                responseBody: $this->responseContent($response),
                headers: $this->responseHeaders($response),
            );
        }
    }

    private function responseStatusCode(ResponseInterface $response): int
    {
        try {
            return $response->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            throw ApiRequestFailed::fromTransportException($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        try {
            $decoded = json_decode($this->responseContent($response), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidApiResponse::invalidJson();
        }

        return $this->ensureArrayResponseData($decoded);
    }

    private function responseContent(ResponseInterface $response): string
    {
        try {
            return $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw ApiRequestFailed::fromTransportException($exception);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function responseHeaders(ResponseInterface $response): array
    {
        try {
            return $response->getHeaders(false);
        } catch (TransportExceptionInterface $exception) {
            throw ApiRequestFailed::fromTransportException($exception);
        }
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
    private function ensureArrayResponseData(mixed $value): array
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
