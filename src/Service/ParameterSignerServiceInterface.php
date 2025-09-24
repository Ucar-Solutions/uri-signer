<?php

namespace UcarSolutions\UriSigner\Service;

use DateTimeInterface;
use UcarSolutions\UriSigner\Entity\KeyInterface;
use UcarSolutions\UriSigner\Entity\VerificationResultInterface;

interface ParameterSignerServiceInterface
{
    public function sign(array $parameters, KeyInterface $key, ?DateTimeInterface $expireDate = null, array $headers = []): string;

    public function verify(string $token, KeyInterface $key): VerificationResultInterface;

}