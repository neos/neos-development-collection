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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;

/**
 * @Flow\Scope("singleton")
 */
class NodeAccessorManager
{

    /**
     * Accessors indexed by ContentStreamIdentifier, DimensionSpacePoint and VisibilityConstraints.
     *
     * For each of the above combinations, only one accessor chain exists.
     *
     * @var array|NodeAccessorInterface[]
     */
    protected $accessors;

    /**
     * @Flow\Inject
     * @var NodeAccessorChainFactory
     */
    protected $nodeAccessorChainFactory;

    // TODO: maybe integrate the visibilityConstraints remembering (which we use right here) in this class, similar to
    // withVisibilityConstraints(..., closure()).

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param VisibilityConstraints $visibilityConstraints
     * @return NodeAccessorInterface
     */
    public function accessorFor(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): NodeAccessorInterface {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash() . '-' . $visibilityConstraints->getHash();
        if (!isset($this->accessors[$index])) {
            $this->accessors[$index] = $this->nodeAccessorChainFactory->build($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints);
        }

        return $this->accessors[$index];
    }
}
