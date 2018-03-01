<?php
namespace Neos\ContentRepository\Domain\Context\Node\Event;

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
use Neos\EventSourcing\Event\EventInterface;

/**
 * A node was specialized
 */
final class NodeWasSpecialized implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var Domain\ValueObject\ContentStreamIdentifier
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
     * @var array|NodeReassignmentMapping[]
     */
    protected $mappings;


    /**
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $specializationIdentifier
     * @param array|NodeReassignmentMapping[] $mappings
     */
    public function __construct(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $specializationIdentifier,
        array $mappings
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->mappings = $mappings;
    }


    /**
     * @return Domain\ValueObject\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\ValueObject\ContentStreamIdentifier
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

    /**
     * @return array|NodeReassignmentMapping[]
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * @param Domain\ValueObject\ContentStreamIdentifier $targetContentStream
     * @return NodeWasSpecialized
     */
    public function createCopyForContentStream(Domain\ValueObject\ContentStreamIdentifier $targetContentStream): NodeWasSpecialized
    {
        return new NodeWasSpecialized(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->targetDimensionSpacePoint,
            $this->specializationIdentifier,
            $this->mappings
        );
    }
}
