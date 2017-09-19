<?php
namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Abstract class for an identifier value object
 */
abstract class AbstractIdentifier implements \JsonSerializable
{
    /**
     * @var UuidInterface
     */
    protected $uuid;

    /**
     * Constructor.
     *
     * @param string $existingIdentifier
     */
    public function __construct(string $existingIdentifier = null)
    {
        if ($existingIdentifier !== null) {
            $this->uuid = Uuid::fromString($existingIdentifier);
        } else {
            $this->uuid = Uuid::uuid4();
        }
    }

    /**
     * @param string $string
     * @return static
     */
    static public function fromString(string $string)
    {
        $instance = new static();
        $instance->uuid = Uuid::fromString($string);
        return $instance;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->uuid->toString();
    }

    /**
     * @return string
     */
    function jsonSerialize()
    {
        return $this->uuid->toString();
    }
}
