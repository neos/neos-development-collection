<?php
namespace Neos\ContentRepository\Domain\Context\Node\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain;

/**
 * Create a generalization of a node in a content stream
 *
 * Copy a node to a generalized dimension space point respecting further generalization mechanisms
 */
final class CreateNodeGeneralization
{
    /**
     * @var \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $nodeIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $targetDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $generalizationIdentifier;


    /**
     * @param \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $generalizationIdentifier
     */
    public function __construct(
        Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $generalizationIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->generalizationIdentifier = $generalizationIdentifier;
    }

    /**
     * @return \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\Context\ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getNodeIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getTargetDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getGeneralizationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->generalizationIdentifier;
    }
}
