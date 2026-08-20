# DummyJSON Users

Framework-agnostic PHP package for retrieving and creating users through the DummyJSON API.

This package targets PHP 8.4 and does not depend on Laravel, Drupal, WordPress, or any other framework runtime.

## Installation

This package is not published to Packagist. Install it from a Git repository by adding a Composer `repositories` entry in the consuming project:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:your-org/dummy-json-users.git"
        }
    ],
    "require": {
        "challenge/dummy-json-users": "dev-main"
    }
}
```

For local development, use a path repository instead:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../dummy-json-users",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "challenge/dummy-json-users": "*"
    }
}
```

Then install dependencies:

```bash
composer install
```

## Usage

```php
<?php

use Challenge\DummyJsonUsers\Service\UserService;

require __DIR__ . '/vendor/autoload.php';

$users = UserService::createDefault();

$user = $users->getUserById(1);
$page = $users->listUsers(page: 1, perPage: 10);
$newUserId = $users->addUser('Ada', 'Lovelace', 'ada@example.com');
```

For dependency injection, pass any Symfony `HttpClientInterface` implementation:

```php
use Challenge\DummyJsonUsers\Service\UserService;
use Symfony\Component\HttpClient\HttpClient;

$service = new UserService(HttpClient::create());
```

The base URL can be overridden for testing or alternate environments:

```php
$service = new UserService($httpClient, 'https://example.test/api');
```

## Custom Requests

This package intentionally models only the user operations required by the assessment. For other DummyJSON endpoints, `request()` provides a small escape hatch without turning the package into a full SDK.

`request()` keeps package-controlled behavior for:

- base URL handling
- Symfony HTTP options
- failed status-code handling
- transport error wrapping
- JSON decoding

It returns the decoded JSON response as an array:

```php
$carts = $users->request('GET', '/carts', [
    'query' => [
        'limit' => 10,
    ],
]);
```

Custom requests do not map responses to DTOs. DTOs are only provided for the modeled user operations.

## DTOs

Users are returned as typed DTOs:

```php
$user->id;
$user->firstName;
$user->lastName;
$user->email;

$user->toArray();
json_encode($user);
```

Only the required fields are mapped from the API response:

- `id`
- `firstName`
- `lastName`
- `email`

## Errors

The service converts HTTP client and API failures into package-specific exceptions:

- `UserNotFound`
- `ApiRequestFailed`
- `InvalidApiResponse`

This keeps remote API failures explicit for developers using the package.

## Testing

Unit tests use Symfony's `MockHttpClient`, so they do not require the live DummyJSON API and do not depend on remote data remaining stable.

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Run PSR-12 style checks:

```bash
composer style
```

Validate Composer metadata:

```bash
composer validate --strict
```

## Local PHP Runtime

The included Dockerfile is only a local development helper. The package itself does not depend on Docker.

Build it:

```bash
docker build -t local-php .
```

Run tools through the container:

```bash
docker run --rm -i --user "$(id -u):$(id -g)" -v "$PWD":/app local-php composer test
```
