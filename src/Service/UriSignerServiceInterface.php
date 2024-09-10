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

use DateTimeInterface;
use Psr\Http\Message\UriInterface;
use UcarSolutions\UriSigner\Entity\KeyInterface;
use UcarSolutions\UriSigner\Entity\VerificationResultInterface;

/**
 * Url Signer interface, used to sign uris with a given key
 */
interface UriSignerServiceInterface
{
    /**
     * @param UriInterface $uri
     * @param KeyInterface $key
     * @param DateTimeInterface|null $expireDate
     * @return UriInterface
     *
     * signs the whole URI
     */
    public function sign(
        UriInterface       $uri,
        KeyInterface       $key,
        ?DateTimeInterface $expireDate = null
    ): UriInterface;

    public function verify(UriInterface $uri, KeyInterface $key): VerificationResultInterface;
}
