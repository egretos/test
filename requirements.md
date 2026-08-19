# Laravel Developer Challenge Requirements

## Objective

Build a framework-agnostic Composer package, targeting PHP 8.4 or newer, that provides a service for retrieving and creating users through the DummyJSON API.

API documentation: <https://dummyjson.com/docs>

Do not publish the package to Packagist or any other Composer package distributor.

## Package Requirements

- The package must be installable and usable as a standalone Composer package.
- The package must be framework agnostic.
- The package must not depend on Laravel, Drupal, WordPress, or any other framework runtime.
- The code must follow clean, modern PSR standards.
- The code must be well typed.
- The package should target PHP 8.4.
- PHPStan static analysis is strongly recommended.
- Well-established standalone Composer packages may be used where appropriate.
- Full frameworks must not be used to solve the task.

## User Service Requirements

Create a service that integrates with the DummyJSON API and supports the following operations:

- Retrieve a single user by ID.
- Retrieve a paginated list of users.
- Add a new user using:
  - first name
  - last name
  - email
- Return the created user's ID after adding a user.

## Data Model Requirements

All users returned by the service must be converted into well-defined DTO models.

Each user DTO must:

- Include only the required user fields:
  - ID
  - first name
  - last name
  - email
- Implement `JsonSerializable`.
- Support conversion to a standard array structure.
- Use typed properties and/or typed constructor arguments.

## Error Handling Requirements

Remote APIs may be unstable or unreliable, so the package must handle API failure scenarios deliberately.

The implementation should consider:

- How API errors are communicated to developers using the package.
- How generic exceptions from an API client or third-party package are converted into domain-specific exceptions.
- Whether exceptions should be allowed to propagate or be caught and wrapped.
- How failed requests, invalid responses, and missing users are represented.

## Testing Requirements

The package must include unit tests for the user service.

Tests should demonstrate that:

- The service can retrieve a single user.
- The service can retrieve a paginated list of users.
- The service can add a new user and return the user ID.
- Returned API data is converted into DTO models.
- DTOs can be serialized to JSON.
- DTOs can be converted to arrays.
- API error handling behaves as expected.

Tests involving the remote API must not depend on the API being online or its live data remaining unchanged.

The test strategy should use one or more of:

- Mocked HTTP clients.
- Fake API responses.
- Stubbed transport layers.
- Recorded fixtures.

Integration or API tests may be included, but they should be separate from deterministic unit tests.

## Acceptance Criteria

- A framework-agnostic Composer package exists.
- The package targets PHP 8.4 or newer.
- A user service exposes methods for:
  - fetching one user by ID
  - fetching a paginated user list
  - creating a user
- The service integrates with DummyJSON.
- User responses are mapped to DTOs.
- DTOs expose JSON serialization and array conversion.
- Domain-specific exceptions are used for API-related failures.
- Unit tests cover the core service behavior.
- Unit tests do not require the live DummyJSON API.
- Code follows modern PHP and PSR conventions.
- PHPStan configuration is included or the codebase is ready for PHPStan analysis.
