<?php
declare(strict_types=1);
/**
 * @package UriSigner
 * @author Dogan Ucar
 *
 *  MIT License
 *
 *  Copyright (c) 2024 Ucar Solutions UG
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 *
 */

namespace Test\Ucarsolutions\UriSigner;

use DateTimeImmutable;
use doganoo\DIP\DateTime\DateTimeService;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Log\NullLogger;
use Ucarsolutions\UriSigner\Entity\KeyInterface;
use Ucarsolutions\UriSigner\Resolver\DefaultParameterNameResolver;
use Ucarsolutions\UriSigner\Resolver\ParameterNameResolverInterface;
use Ucarsolutions\UriSigner\Service\UriSignerService;
use Ucarsolutions\UriSigner\Service\UriSignerServiceInterface;

class UriSignerServiceTest extends TestCase
{
    private UriSignerServiceInterface $signerService;
    private ParameterNameResolverInterface $parameterNameResolver;
    private KeyInterface $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parameterNameResolver = new DefaultParameterNameResolver();
        $this->signerService = new UriSignerService(
            $this->parameterNameResolver,
            new DateTimeService(),
            new NullLogger()
        );

        $this->key = new class implements KeyInterface {

            public function getKey(): string
            {
                return "t0psecret";
            }
        };
    }

    public function testSign(): void
    {
        $uri = $this->signerService->sign(
            new Uri("https://example.com?var=test"),
            $this->key
        );

        $this->assertTrue($uri instanceof UriInterface);
        $this->assertTrue($uri->getScheme() === 'https');
        $this->assertTrue($uri->getHost() === 'example.com');
        $this->assertTrue(str_contains($uri->getQuery(), 'var=test&' . $this->parameterNameResolver->getSignature() . '='));
    }

    public function testSignUrlWithValidKeyAndExpiry(): void
    {
        $uri = new Uri('https://example.com/resource');
        $signedUri = $this->signerService->sign($uri, $this->key);
        $this->assertStringContainsString($this->parameterNameResolver->getSignature() . '=', (string)$signedUri);
    }

    public function testVerifySignedUrlWithCorrectSignature(): void
    {
        $uri = new Uri(
            sprintf('https://example.com/resource?%s=valid-token', $this->parameterNameResolver->getSignature())
        );
        $verificationResult = $this->signerService->verify($uri, $this->key);
        $this->assertFalse($verificationResult->isVerified());
    }

    public function testVerifyFailsIfSignatureIsMissing(): void
    {
        $uri = new Uri('https://example.com/resource');
        $verificationResult = $this->signerService->verify($uri, $this->key);
        $this->assertFalse($verificationResult->isVerified());
    }

    public function testVerifyFailsIfUrlIsModified(): void
    {
        $originalUri = new Uri('https://example.com/resource?param=value');
        $signedUri = $this->signerService->sign($originalUri, $this->key);
        $modifiedUri = new Uri('https://example.com/modified-resource?p1=v2&' . $signedUri->getQuery());
        $verificationResult = $this->signerService->verify($modifiedUri, $this->key);
        $this->assertFalse($verificationResult->isVerified());
    }

    public function testVerifyFailsIfSignatureIsInvalid(): void
    {
        $uri = new Uri(sprintf('https://example.com/resource?%s=invalid-token', $this->parameterNameResolver->getSignature()));
        $verificationResult = $this->signerService->verify($uri, $this->key);
        $this->assertFalse($verificationResult->isVerified());
    }

    public function testSignUrlWithoutQueryParams(): void
    {
        $uri = new Uri('https://example.com/resource');
        $signedUri = $this->signerService->sign($uri, $this->key);
        $this->assertStringContainsString(sprintf('?%s=', $this->parameterNameResolver->getSignature()), (string)$signedUri);
    }

    public function testVerifyFailsIfUrlIsExpired(): void
    {
        $uri = new Uri('https://example.com/resource');
        $expiredDate = (new DateTimeImmutable())->modify('-10 minutes');
        $signedUri = $this->signerService->sign($uri, $this->key, $expiredDate);
        $verificationResult = $this->signerService->verify($signedUri, $this->key);
        $this->assertFalse($verificationResult->isVerified());
    }


}
