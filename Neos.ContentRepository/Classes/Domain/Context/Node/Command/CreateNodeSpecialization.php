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
 * Create a specialization of a node in a content stream
 *
 * Copy a node to a specialized dimension space point respecting further specialization mechanisms
 */
final class CreateNodeSpecialization
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
    protected $specializationIdentifier;


    /**
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $specializationIdentifier
     */
    public function __construct(
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $specializationIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->specializationIdentifier = $specializationIdentifier;
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
    public function getSpecializationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->specializationIdentifier;
    }
}
