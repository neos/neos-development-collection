<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * All variants in a NodeAggregate have the same NodeName - and this can be changed here.
 * This is the case because Node Names are usually only used for tethered nodes (=autocreated in the old CR);
 * as then the Node Name is used for querying.
 */
final class ChangeNodeAggregateName implements \JsonSerializable, RebasableToOtherContentStreamsInterface, MatchableWithNodeAddressInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeName
     */
    private $newNodeName;

    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, NodeName $newNodeName)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newNodeName = $newNodeName;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeName::fromString($array['newNodeName'])
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewNodeName(): NodeName
    {
        return $this->newNodeName;
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'newNodeName' => $this->newNodeName,
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new ChangeNodeAggregateName(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newNodeName
        );
    }

    public function matchesNodeAddress(NodeAddress $nodeAddress): bool
    {
        return (
            (string)$this->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
            && $this->getNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
        );
    }
}
