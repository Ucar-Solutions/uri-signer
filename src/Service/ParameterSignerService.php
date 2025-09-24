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
use doganoo\DI\DateTime\DateTimeServiceInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use UcarSolutions\UriSigner\Entity\KeyInterface;
use UcarSolutions\UriSigner\Entity\VerificationResult;
use UcarSolutions\UriSigner\Entity\VerificationResultInterface;
use UnexpectedValueException;

/**
 * UriSigner class - this class signs an entire URL or, if needed,
 * just the query parameters
 */
class ParameterSignerService implements ParameterSignerServiceInterface
{

    public function __construct(
        private readonly DateTimeServiceInterface $dateTimeService,
        private readonly LoggerInterface          $logger
    )
    {
    }

    public function sign(
        array              $parameters,
        KeyInterface       $key,
        ?DateTimeInterface $expireDate = null,
        array              $headers = []
    ): string
    {
        if ($expireDate === null) {
            $expireDate = (new DateTimeImmutable())->modify("+3 minute");
        }

        return JWT::encode(
            [
                'iss' => 'https://ucar-solutions.de/uri-signer',
                'iat' => time(),
                'exp' => $expireDate->getTimestamp(),
                'sub' => 'Signed Payload',
                'jti' => Uuid::uuid4(),
                'uid' => Uuid::uuid4(),
                'data' => $parameters
            ],
            $key->getKey(),
            'HS256',
            null,
            $headers
        );
    }

    public function verify(string $token, KeyInterface $key): VerificationResultInterface
    {
        try {
            $decoded = (array)JWT::decode($token, new Key($key->getKey(), 'HS256'));
            $exp = isset($decoded['exp']) ? (new DateTimeImmutable())->setTimestamp((int)$decoded['exp']) : null;

            if ($exp !== null && $this->dateTimeService->isExpired($exp)) {
                return new VerificationResult(false);
            }

            return new VerificationResult(true);
        } catch (UnexpectedValueException $e) {
            $this->logger->warning('invalid token', ['exception' => $e]);
            return new VerificationResult(false);
        }
    }

}
