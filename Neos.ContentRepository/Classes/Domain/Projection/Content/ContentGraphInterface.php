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
use Neos\Flow\Annotations as Flow;
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
}
