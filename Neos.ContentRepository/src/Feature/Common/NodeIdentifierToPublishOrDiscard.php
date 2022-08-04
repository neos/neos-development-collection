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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * A node identifier (Content Stream, NodeAggregateIdentifier, DimensionSpacePoint); used when
 * publishing or discarding individual nodes ({@see PublishIndividualNodesFromWorkspace} and
 * {@see DiscardIndividualNodesFromWorkspace}
 * and the corresponding events).
 */
final class NodeIdentifierToPublishOrDiscard implements \JsonSerializable
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly DimensionSpacePoint $dimensionSpacePoint,
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'dimensionSpacePoint' => $this->dimensionSpacePoint,
        ];
    }
}
