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
 * A price specification value object as an example for an array-based but stringable property value
 *
 * @see https://schema.org/PriceSpecification
 */
final readonly class PriceSpecification implements \Stringable
{
    private function __construct(
        public float $price,
        public string $priceCurrency,
        public bool $valueAddedTaxIncluded
    ) {
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['price'],
            $array['priceCurrency'],
            $array['valueAddedTaxIncluded'] ?? true, // default value
        );
    }

    public static function dummy(): self
    {
        return new self(
            13.37,
            'EUR',
            true
        );
    }

    public static function anotherDummy(): self
    {
        return new self(
            84.72,
            'EUR',
            false
        );
    }

    public function __toString(): string
    {
        return $this->price . ' ' . $this->priceCurrency;
    }
}
