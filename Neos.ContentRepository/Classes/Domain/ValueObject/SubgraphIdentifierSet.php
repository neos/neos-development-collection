<?php

namespace Neos\ContentRepository\Domain\ValueObject;

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
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\ContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\SubgraphIdentifier;
use Neos\Flow\Annotations as Flow;

final class SubgraphIdentifierSet
{
    /**
     * @var array<SubgraphIdentifier>
     */
    private $subgraphIdentifiers = [];

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePointSet $dimensionSpacePointSet)
    {
        foreach ($dimensionSpacePointSet->getPoints() as $point) {
            /* @var $point \Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint */
            $this->subgraphIdentifiers[] = new SubgraphIdentifier($contentStreamIdentifier, $point);
        }
    }

    /**
     * @return array|SubgraphIdentifier[]
     */
    public function getSubgraphIdentifiers()
    {
        return $this->subgraphIdentifiers;
    }
}
