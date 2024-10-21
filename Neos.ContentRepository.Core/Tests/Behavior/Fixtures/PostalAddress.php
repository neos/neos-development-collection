<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Fixtures;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
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
final readonly class PostalAddress
{
    private function __construct(
        public string $streetAddress,
        public string $postalCode,
        public string $addressLocality,
        public string $addressCountry
    ) {
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['streetAddress'] ?? throw new \InvalidArgumentException('streetAddress is not set.'),
            $array['postalCode'] ?? throw new \InvalidArgumentException('postalCode is not set.'),
            $array['addressLocality'] ?? throw new \InvalidArgumentException('addressLocality is not set.'),
            $array['addressCountry'] ?? throw new \InvalidArgumentException('addressCountry is not set.')
        );
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

    public static function anotherDummy(): self
    {
        return new self(
            '29 31st of February Street',
            '12346',
            'Another city',
            'Another country'
        );
    }
}
