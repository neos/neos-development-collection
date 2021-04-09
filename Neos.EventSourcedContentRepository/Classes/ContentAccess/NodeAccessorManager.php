<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\ContentAccess\Delegating\AccessorRegistry;
use Neos\EventSourcedContentRepository\ContentAccess\Delegating\DelegatingAccessor;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;

/**
 * @Flow\Scope("singleton")
 */
class NodeAccessorManager
{

    /**
     * @var array|NodeAccessorInterface[]
     */
    protected $accessors;

    protected $accessorRegistry;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param VisibilityConstraints $visibilityConstraints
     * @return ContentSubgraphInterface
     */
    public function getAccessor(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): NodeAccessorInterface {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash() . '-' . $visibilityConstraints->getHash();
        if (!isset($this->accessors[$index])) {
            // TODO: always "DelegatingAccessor" here is correct IMHO
            $this->accessors[$index] = new DelegatingAccessor(
                new AccessorRegistry($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints)
            );
        }

        return $this->accessors[$index];
    }
}
