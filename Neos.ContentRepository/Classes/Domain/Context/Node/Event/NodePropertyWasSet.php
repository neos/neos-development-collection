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

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\EventSourcing\Event\EventInterface;

/**
 * Node property was set event
 */
final class NodePropertyWasSet implements EventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var PropertyValue
     */
    private $value;

    /**
     * NodePropertyWasSet constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param string $propertyName
     * @param PropertyValue $value
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        $propertyName,
        PropertyValue $value
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->value = $value;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @return PropertyValue
     */
    public function getValue(): PropertyValue
    {
        return $this->value;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodePropertyWasSet
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodePropertyWasSet(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->propertyName,
            $this->value
        );
    }
}
