# DummyJSON Users

Framework-agnostic PHP package for retrieving and creating users through the DummyJSON API.

This package targets PHP 8.4 and does not depend on Laravel, Drupal, WordPress, or any other framework runtime.

## Installation

Install dependencies locally:

```bash
composer install
```

If you are using the included Docker PHP runtime:

```bash
source ./php-aliases.sh
composer install
```

## Usage

```php
<?php

use Challenge\DummyJsonUsers\Service\UserService;

require __DIR__ . '/vendor/autoload.php';

$users = UserService::createHttpClient();

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

Validate Composer metadata:

```bash
composer validate
```

## Local PHP Runtime

The included Dockerfile provides PHP 8.4 with Composer and PHPUnit-required extensions.

Build it:

```bash
docker build -t local-php .
```

Load project-local aliases:

```bash
source ./php-aliases.sh
```

Then use PHP and Composer from this directory:

```bash
php -v
composer test
```
