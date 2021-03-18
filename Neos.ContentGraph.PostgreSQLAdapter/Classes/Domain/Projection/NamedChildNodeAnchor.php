<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * The named child node anchor value object
 *
 * @Flow\Proxy(false)
 */
final class NamedChildNodeAnchor implements \JsonSerializable
{
    private ?NodeName $name;

    private NodeRelationAnchorPoint $childNodeAnchor;

    private function __construct(?NodeName $name, NodeRelationAnchorPoint $childNodeAnchor)
    {
        $this->name = $name;
        $this->childNodeAnchor = $childNodeAnchor;
    }

    public static function create(?NodeName $name): self
    {
        return new self($name, NodeRelationAnchorPoint::create());
    }

    public static function fromArray(array $array): self
    {
        return new self(NodeName::fromString($array['name']), NodeRelationAnchorPoint::fromString($array['childNodeAnchor']));
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'childNodeAnchor' => $this->childNodeAnchor
        ];
    }
}
