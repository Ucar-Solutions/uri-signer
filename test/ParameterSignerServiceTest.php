<?php
declare(strict_types=1);
/**
 * @package UriSigner (tests)
 * @author …
 */

namespace Test\UcarSolutions\UriSigner;

use DateTimeImmutable;
use doganoo\DIP\DateTime\DateTimeService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key as JwtKey;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use UcarSolutions\UriSigner\Entity\KeyInterface;
use UcarSolutions\UriSigner\Service\ParameterSignerService;
use UcarSolutions\UriSigner\Service\ParameterSignerServiceInterface;

final class ParameterSignerServiceTest extends TestCase
{
    private ParameterSignerServiceInterface $signer;
    private KeyInterface $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = new ParameterSignerService(
            new DateTimeService(),
            new NullLogger()
        );

        $this->key = new class implements KeyInterface {
            public function getKey(): string
            {
                return 't0psecret-that-is-long-enough-for-hs256';
            }
        };
    }

    public function testSignReturnsJwtContainingClaims(): void
    {
        $params = ['leadId' => '123', 'list' => 'marketing', 'aud' => 'dmarcflow.com'];
        $token = $this->signer->sign($params, $this->key);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Decode once to inspect claims (library-level check)
        $decoded = (array)JWT::decode($token, new JwtKey($this->key->getKey(), 'HS256'));

        $this->assertSame('https://ucar-solutions.de/uri-signer', $decoded['iss']);
        $this->assertSame('Signed Payload', $decoded['sub']);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertGreaterThan(time(), (int)$decoded['exp']);

        // jti/uid should be UUIDs (ramsey/uuid json-serializes to strings)
        $this->assertTrue(Uuid::isValid((string)$decoded['jti']));
        $this->assertTrue(Uuid::isValid((string)$decoded['uid']));

        // Custom data payload preserved
        $this->assertEquals($params, (array)($decoded['data'] ?? []));
    }

    public function testVerifyValidTokenReturnsTrue(): void
    {
        $token = $this->signer->sign(
            ['purpose' => 'unsubscribe', 'leadId' => 'abc'],
            $this->key,
            (new DateTimeImmutable())->modify('+10 minutes')
        );

        $result = $this->signer->verify($token, $this->key);
        $this->assertTrue($result->isVerified());
    }

    public function testVerifyExpiredTokenReturnsFalse(): void
    {
        $token = $this->signer->sign(
            ['purpose' => 'unsubscribe', 'leadId' => 'abc'],
            $this->key,
            (new DateTimeImmutable())->modify('-1 minute')
        );

        $result = $this->signer->verify($token, $this->key);
        $this->assertFalse($result->isVerified());
    }

    public function testVerifyWithWrongKeyReturnsFalse(): void
    {
        $token = $this->signer->sign(['x' => 1], $this->key);

        $wrongKey = new class implements KeyInterface {
            public function getKey(): string
            {
                return 'wr0ngsecret-that-is-long-enough-for-hs256';
            }
        };

        $result = $this->signer->verify($token, $wrongKey);
        $this->assertFalse($result->isVerified());
    }

    public function testVerifyWithMalformedTokenReturnsFalse(): void
    {
        $result = $this->signer->verify('not-a-jwt', $this->key);
        $this->assertFalse($result->isVerified());
    }
}
