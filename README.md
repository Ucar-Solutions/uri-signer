# URL Signer for Vanilla PHP

A URL signer implementation in **PHP** that generates secure, signed URLs with an expiration date. This package allows you to sign full URLs or just query parameters, adding a layer of security for accessing resources or sharing sensitive information.

![Build Status](https://github.com/Ucar-Solutions/uri-signer/workflows/Run%20Unit%20Tests/badge.svg)
![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)


## Features

- Sign the full URI or only query parameters
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

You can sign a full URL or only query parameters and include an expiration date. The URL will be valid until the specified expiration time, after which it becomes invalid.

```php
use Ucarsolutions\UriSigner\Service\SignerService;

$uri = new Laminas\Diactoros\Uri('https://example.com/resource');
$key = new Ucarsolutions\UriSigner\Entity\Key('your-secret-key');

$signer = new SignerService();

// Sign the entire URI with expiration
$signedUri = $signer->signUri($uri, $key);

// Result: https://example.com/resource?__us__url__signer__signature=xyz&__us__url__signer__expire_date=1234567890
echo $signedUri;
```
Example Signing Only Query Paramters

```php
use Ucarsolutions\UriSigner\Service\SignerService;

$uri = new Laminas\Diactoros\Uri('https://example.com/resource?foo=bar');
$key = new Ucarsolutions\UriSigner\Entity\Key('your-secret-key');

$signer = new SignerService();

// Sign the query parameters with expiration
$signedUri = $signer->signParameters($uri, $key);

// Result: https://example.com/resource?foo=bar&__us__url__signer__signature=xyz&__us__url__signer__expire_date=1234567890
echo $signedUri;
```

## Expiration
The expiration date is added to the URL as a query parameter and is included in the signed data to ensure the URL becomes invalid after the expiration time.

If no expiration date is provided, a default of 3 minutes from the current time is used.

## Configuration
You can configure the expiration time and the secret key for signing URLs.

## Default Expiration Time
You can change the default expiration time from 3 minutes to any other value by adjusting the configuration in the service or manually passing a DateTimeInterface to the sign function.

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
