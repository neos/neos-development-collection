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
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * The abstract content graph
 */
abstract class AbstractContentGraph implements ContentProjection\ContentGraphInterface
{
    /**
     * @var array|ContentSubgraphInterface[]
     */
    protected $subgraphs;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentSubgraphInterface
    {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash();
        if (!isset($index)) {
            $this->subgraphs[$index] = $this->createSubgraph($contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $this->subgraphs[$index];
    }

    /**
     * @return array|ContentSubgraphInterface[]
     */
    final public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface
     */
    abstract protected function createSubgraph(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): ContentSubgraphInterface;
}
