<?php

namespace Neos\ContentRepository\Domain\Projection\Content;

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
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain;

/**
 * The interface to be implemented by content graphs
 */
interface ContentGraphInterface
{
    /**
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    public function getSubgraphByIdentifier(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentSubgraphInterface;

    /**
     * @return array|ContentSubgraphInterface[]
     */
    public function getSubgraphs(): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifierInContentStream(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): ?NodeInterface;
}
