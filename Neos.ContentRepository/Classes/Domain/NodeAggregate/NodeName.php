<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The Node name is the "path part" of the node; i.e. when accessing the node "/foo" via path,
 * the node name is "foo".
 *
 * Semantically it describes the hierarchical relation of a node to its parent, e.g. "main" denotes the main child node.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class NodeName implements \JsonSerializable
{
    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        if (!is_string($value) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $value) !== 1) {
            throw new \InvalidArgumentException('Invalid node name "' . $value . '" (a node name must only contain lowercase characters, numbers and the "-" sign).', 1364290748);
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new static(strtolower($value));
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value ?? '';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
