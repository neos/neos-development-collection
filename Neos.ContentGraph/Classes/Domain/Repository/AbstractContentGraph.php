<?php

namespace Neos\ContentGraph\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\Flow\Annotations as Flow;

/**
 * The abstract content graph
 */
abstract class AbstractContentGraph implements ContentProjection\ContentGraphInterface
{
    /**
     * @var array|ContentProjection\ContentSubgraphInterface[]
     */
    protected $subgraphs;

    /**
     * @param ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint
     * @return ContentProjection\ContentSubgraphInterface|null
     */
    final public function getSubgraphByIdentifier(
        ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentProjection\ContentSubgraphInterface
    {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash();
        if (!isset($index)) {
            $this->subgraphs[$index] = $this->createSubgraph($contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $this->subgraphs[$index];
    }

    /**
     * @return array|ContentProjection\ContentSubgraphInterface[]
     */
    final public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    /**
     * @param ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint
     * @return ContentProjection\ContentSubgraphInterface
     */
    abstract protected function createSubgraph(ContentRepository\ValueObject\ContentStreamIdentifier $contentStreamIdentifier, ContentRepository\ValueObject\DimensionSpacePoint $dimensionSpacePoint): ContentProjection\ContentSubgraphInterface;
}
