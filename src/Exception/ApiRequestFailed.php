<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Exception;

use Throwable;

final class ApiRequestFailed extends DummyJsonUsersException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?string $responseBody = null,
        private readonly ?string $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * @param array<string, list<string>> $headers
     */
    public static function forStatusCode(int $statusCode, string $responseBody, array $headers = []): self
    {
        $retryAfter = $headers['retry-after'][0] ?? null;

        $message = sprintf('DummyJSON API request failed with status code %d.', $statusCode);

        if ($retryAfter !== null) {
            $message .= sprintf(' Retry-After: %s.', $retryAfter);
        }

        if ($responseBody !== '') {
            $message .= sprintf(' Response body: %s', $responseBody);
        }

        return new self(
            message: $message,
            statusCode: $statusCode,
            responseBody: $responseBody,
            retryAfter: $retryAfter,
        );
    }

    public static function fromTransportException(Throwable $exception): self
    {
        return new self(
            message: 'DummyJSON API request failed before a complete response was received.',
            previous: $exception,
        );
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
