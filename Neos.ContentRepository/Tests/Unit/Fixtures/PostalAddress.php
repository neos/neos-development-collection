<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Unit\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A postal address value object
 *
 * @see https://schema.org/PostalAddress
 */
final class PostalAddress
{
    private string $streetAddress;

    private string $postalCode;

    private string $addressLocality;

    private string $addressCountry;

    public function __construct(
        string $streetAddress,
        string $postalCode,
        string $addressLocality,
        string $addressCountry
    ) {
        $this->streetAddress = $streetAddress;
        $this->postalCode = $postalCode;
        $this->addressLocality = $addressLocality;
        $this->addressCountry = $addressCountry;
    }

    public static function dummy(): self
    {
        return new self(
            '28 31st of February Street',
            '12345',
            'City',
            'Country'
        );
    }

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getAddressLocality(): string
    {
        return $this->addressLocality;
    }

    public function getAddressCountry(): string
    {
        return $this->addressCountry;
    }
}
