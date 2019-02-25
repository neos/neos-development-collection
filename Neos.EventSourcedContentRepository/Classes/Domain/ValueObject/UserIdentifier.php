<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;

/**
 * User Identifier
 * @Flow\Proxy(false)
 */
final class UserIdentifier implements \JsonSerializable
{
    const SYSTEM_USER_IDENTIFIER = 'system';

    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function create(): self
    {
        return new static(Algorithms::generateUUID());
    }

    /**
     * Creates a special user identifier which refers to the virtual "system" user.
     */
    public static function forSystemUser(): self
    {
        return new static(self::SYSTEM_USER_IDENTIFIER);
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    /**
     * @return bool
     */
    public function isSystemUser(): bool
    {
        return $this->value === self::SYSTEM_USER_IDENTIFIER;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
