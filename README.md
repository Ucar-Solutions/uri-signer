# URL Signer for Vanilla PHP

A URL signer implementation in **PHP** that generates secure, signed URLs with an expiration date. This package allows you to sign full URLs or just query parameters, adding a layer of security for accessing resources or sharing sensitive information.

![Build Status](https://github.com/Ucar-Solutions/uri-signer/workflows/Run%20Unit%20Tests/badge.svg)
![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)


## Features

- Sign a given URI
- Include an expiration date as part of the signature
- Ensure URL integrity and prevent unauthorized modifications
- Easy integration with **Laminas** or other PHP-based frameworks

## Installation

Install the package via Composer:

```bash
composer require ucarsolutions/uri-signer
```

## Usage

### Sign URL with Expiration Date

You can sign the uri with: 

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$signerService = new \Ucarsolutions\UriSigner\Service\UriSignerService(
    new \Ucarsolutions\UriSigner\Resolver\DefaultParameterNameResolver(),
    new \doganoo\DIP\DateTime\DateTimeService(),
    new \Psr\Log\NullLogger()
);

$key = new class implements \Ucarsolutions\UriSigner\Entity\KeyInterface {

    public function getKey(): string
    {
        return "t0psecret";
    }
};
$uri = $signerService->sign(
    new \Laminas\Diactoros\Uri("https://example.com"),
    $key
);
dump((string)$uri); // https://example.com/?__us_signature=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3VjYXItc29sdXRpb25zLmRlL3VyaS1zaWduZXIiLCJleHAiOjE3MjU5MDYzMDQsInN1YiI6IlNpZ25lZCBVUkwiLCJ1cmwiOiJodHRwczovL2V4YW1wbGUug29tIiwidWlkIjoiNzM3YTgwNzAtZGU5MS00MTQ3LWohYmMtZTY1OWZiOGZmNWZyIn0.CH7E-fHYhtfGHUljB85dIWL-ZYGr8wRMVef0gY_SRLE
```
Example Verifying with `$uri` above:

```php
<?php
$result = $signerService->verify($uri,$key);
dump($result->isVerified());
```

## Expiration
The expiration date is added to the signature and is included in the signed data to ensure the URL becomes invalid after the expiration time.

If no expiration date is provided, a default of 3 minutes from the current time is used.

## Configuration
You can configure the expiration time and the secret key for signing URLs.

## Tests
Run the tests with PHPUnit:

```bash
vendor/bin/phpunit
```
## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any suggestions or bug reports.

Contribution Guidelines:
1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Write tests for your changes.
4. Make sure all tests pass.
5. Submit a pull request.

## License
This project is licensed under the MIT License. See the LICENSE file for details.
