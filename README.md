This library provides a simple and framework-agnostic interface for managing users via https://reqres.in/ dummy API. It allows for retrieving a single user by ID, retrieving a paginated list of users, and creating new users.

- Framework-agnostic: Use it with Laravel, Symfony, or any PHP project
- Implements PSR-7, PSR-17, and PSR-18 for HTTP messaging and client abstraction
- Exception handling and logging based on PSR-3

### Installation

Use Composer to install the library in your project:

```bash
composer require your-vendor/user-service-package
```

Ensure you have a PSR-18 compatible HTTP client and PSR-17 HTTP factories installed in your project. If you're unsure, you can install Guzzle, which implements these PSR interfaces:

```bash
composer require guzzlehttp/guzzle http-interop/http-factory-guzzle
```

### Key Considerations
- Throws domain-specific exceptions for various error conditions (e.g., UserNotFoundException). Users should catch these exceptions to handle error conditions appropriately.
- Logs http client related errors, which assists in debugging and monitoring the library's usage in production environments.
- By adhering to PSR-7, PSR-17, and PSR-18 standards, the library ensures compatibility and flexibility across different HTTP client implementations.

### License

This library is open-sourced software licensed under the MIT license.

