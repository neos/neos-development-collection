<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentGraph\Domain\Repository\AbstractContentGraph;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\Flow\Annotations as Flow;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class ContentGraph extends AbstractContentGraph
{
    protected function createSubgraph(string $editingSessionName, ContentRepository\ValueObject\DimensionValueCombination $dimensionValues): ContentProjection\ContentSubgraphInterface
    {
        return new ContentSubgraph($editingSessionName, $dimensionValues);
    }
}
