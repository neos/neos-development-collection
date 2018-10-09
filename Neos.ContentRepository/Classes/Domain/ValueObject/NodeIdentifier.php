<?php
namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class NodeIdentifier implements \JsonSerializable
{
    /**
     * @var UuidInterface
     */
    protected $uuid;

    /**
     * Constructor.
     *
     * @param string $existingIdentifier
     * @throws \Exception
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->uuid->toString();
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->uuid->toString();
    }
}
