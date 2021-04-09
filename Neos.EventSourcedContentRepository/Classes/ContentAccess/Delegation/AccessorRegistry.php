<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess\Delegating;

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
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\Parts\FindChildNodesInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * @internal
 */
class AccessorRegistry
{

    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var VisibilityConstraints
     */
    protected $visibilityConstraints;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibilityConstraints = $visibilityConstraints;
    }

    public function findMatchingAccessor(string $methodName, NodeInterface $startingNode): mixed
    {

        $this->
        if (!isset($this->accessors[$index])) {
            // TODO: always "DelegatingAccessor" here is correct IMHO
            $this->accessors[$index] = new DelegatingAccessor(
                new AccessorRegistry($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints)
            );
        }

        return $this->accessors[$index];


    }
}
