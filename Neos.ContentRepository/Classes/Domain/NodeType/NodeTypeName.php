<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Name of a Node Type; e.g. "Neos.Neos:Content"
 *
 * @Flow\Proxy(false)
 * @api
 */
final class NodeTypeName implements \JsonSerializable
{
    const ROOT_NODE_TYPE_NAME = 'Neos.ContentRepository:Root';

    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Node type name must not be empty.', 1505835958);
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(NodeTypeName $other): bool
    {
        return $this->value === $other->getValue();
    }

    public function jsonSerialize()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
