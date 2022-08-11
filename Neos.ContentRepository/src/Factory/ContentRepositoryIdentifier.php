<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Factory;

final class ContentRepositoryIdentifier
{
    private function __construct(
        public readonly string $value
    ) {
        if (!preg_match('/^[a-z][a-z\d_]*[a-z]$/', $this->value)) {
            throw new \InvalidArgumentException(
                'Content Repository identifiers must be only lowercase and with _ and 0-9. ' .
                'This is to ensure this works inside a database table name properly.'
            );
        }
        if (strlen($this->value) >= 16) {
            throw new \InvalidArgumentException(
                'Content Repository identifiers shorter than 16 characters. ' .
                'This is to ensure this works inside a database table name properly.'
            );
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
