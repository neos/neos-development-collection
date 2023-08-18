<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\Helpers;

/**
 * The testing node aggregate identifier value object
 */
final class TestingNodeAggregateId
{
    const NON_EXISTENT = 'i-do-not-exist';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public static function nonExistent(): self
    {
        return new self(self::NON_EXISTENT);
    }

    public function isNonExistent(): bool
    {
        return $this->value === self::NON_EXISTENT;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
