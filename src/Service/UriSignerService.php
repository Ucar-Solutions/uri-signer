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

namespace UcarSolutions\UriSigner\Service;

use DateTimeImmutable;
use DateTimeInterface;
use doganoo\DI\DateTime\IDateTimeService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use UcarSolutions\UriSigner\Entity\KeyInterface;
use UcarSolutions\UriSigner\Entity\VerificationResult;
use UcarSolutions\UriSigner\Entity\VerificationResultInterface;
use UcarSolutions\UriSigner\Exception\InvalidUrlException;
use UcarSolutions\UriSigner\Exception\SignatureNotFoundException;
use UcarSolutions\UriSigner\Resolver\ParameterNameResolverInterface;
use UnexpectedValueException;

/**
 * UriSigner class - this class signs an entire URL or, if needed,
 * just the query parameters
 */
class UriSignerService implements UriSignerServiceInterface
{

    public function __construct(
        private readonly ParameterNameResolverInterface $parameterNameResolver,
        private readonly IDateTimeService               $dateTimeService,
        private readonly LoggerInterface                $logger
    )
    {
    }

    /**
     * @param UriInterface $uri
     * @param KeyInterface $key
     * @param DateTimeInterface|null $expireDate
     * @return UriInterface
     *
     * signs the entire URL
     */
    public function sign(
        UriInterface       $uri,
        KeyInterface       $key,
        ?DateTimeInterface $expireDate = null
    ): UriInterface
    {
        if ($expireDate === null) {
            $expireDate = (new DateTimeImmutable())->modify("+3 minute");
        }

        $signature = JWT::encode(
            [
                'iss' => 'https://ucar-solutions.de/uri-signer',
                'exp' => $expireDate->getTimestamp(),
                'sub' => 'Signed URL',
                'url' => (string)$uri,
                'uid' => Uuid::uuid4()
            ],
            $key->getKey(),
            'HS256'
        );

        $queryParams = [];
        parse_str($uri->getQuery(), $queryParams);

        $queryParams[$this->parameterNameResolver->getSignature()] = $signature;
        $newQuery = http_build_query($queryParams);

        return $uri->withQuery($newQuery);
    }

    public function verify(UriInterface $uri, KeyInterface $key): VerificationResultInterface
    {
        try {
            $parameters = [];
            parse_str($uri->getQuery(), $parameters);

            $signature = $parameters[$this->parameterNameResolver->getSignature()] ?? null;

            if ($signature === null) {
                throw new SignatureNotFoundException();
            }

            $decoded = (array)JWT::decode(
            /** @phpstan-ignore-next-line */
                (string)$signature,
                new Key(
                    $key->getKey(),
                    'HS256'
                )
            );

            $originalUrl = $decoded['url'] ?? null;
            $currentUriWithoutSignature = $uri->withQuery(
                http_build_query(
                    array_diff_key(
                        $parameters,
                        [
                            $this->parameterNameResolver->getSignature() => ''
                        ]
                    )
                )
            );

            if ($originalUrl !== (string)$currentUriWithoutSignature) {
                throw new InvalidUrlException('The URL has been modified');
            }

            $expireDate = (new DateTimeImmutable())->setTimestamp($decoded['exp']);
            return new VerificationResult(
                $this->dateTimeService->isExpired($expireDate)
            );
        } catch (SignatureNotFoundException|InvalidUrlException|UnexpectedValueException $e) {
            $this->logger->error('error verifying signature', ['exception' => $e]);
            return new VerificationResult(false);
        }
    }

}
