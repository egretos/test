<?php

declare(strict_types=1);

namespace Challenge\DummyJsonUsers\Tests\Support;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpRequestRecorder
{
    /**
     * @var list<array{method: string, url: string, options: array<string, mixed>}>
     */
    private array $requests = [];

    /**
     * @param list<MockResponse> $responses
     */
    public function __construct(
        private array $responses = [],
    ) {
    }

    public function client(): MockHttpClient
    {
        return new MockHttpClient(
            function (string $method, string $url, array $options): ResponseInterface {
                $this->requests[] = [
                    'method' => $method,
                    'url' => $url,
                    'options' => $this->normalizeOptions($options),
                ];

                return array_shift($this->responses) ?? new MockResponse('', ['http_code' => 500]);
            },
        );
    }

    /**
     * @param array<int|string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    public function assertNoRequests(): void
    {
        \PHPUnit\Framework\Assert::assertSame([], $this->requests);
    }

    public function assertRequest(string $method, string $url, ?string $jsonBody = null): void
    {
        \PHPUnit\Framework\Assert::assertCount(1, $this->requests);

        $request = $this->requests[0];

        \PHPUnit\Framework\Assert::assertSame($method, $request['method']);
        \PHPUnit\Framework\Assert::assertSame($url, $request['url']);

        if ($jsonBody === null) {
            return;
        }

        \PHPUnit\Framework\Assert::assertArrayHasKey('body', $request['options']);
        \PHPUnit\Framework\Assert::assertIsString($request['options']['body']);
        \PHPUnit\Framework\Assert::assertJsonStringEqualsJsonString($jsonBody, $request['options']['body']);
    }
}
