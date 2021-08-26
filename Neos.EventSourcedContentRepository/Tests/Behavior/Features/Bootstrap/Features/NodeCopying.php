<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\Features;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper\NodesByAdapter;

/**
 * The node copying trait for behavioral tests
 */
trait NodeCopying
{
    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getContentGraphs(): ContentGraphs;

    abstract protected function getCurrentNodes(): ?NodesByAdapter;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When /^the command CopyNodesRecursively is executed, copying the current node aggregate with payload:$/
     */
    public function theCommandCopyNodesRecursivelyIsExecutedCopyingTheCurrentNodeAggregateWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentGraphs = $this->getContentGraphs();
        $contentGraph = reset($contentGraphs);
        $subgraph = $contentGraph->getSubgraphByIdentifier(
            $this->getCurrentContentStreamIdentifier(),
            $this->getCurrentDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $currentNodes = $this->getCurrentNodes();
        $currentNode = reset($currentNodes);
        $targetDimensionSpacePoint = isset($commandArguments['targetDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $targetSucceedingSiblingNodeAggregateIdentifier = isset($commandArguments['targetSucceedingSiblingNodeAggregateIdentifier'])
            ? NodeAggregateIdentifier::fromString($commandArguments['targetSucceedingSiblingNodeAggregateIdentifier'])
            : null;
        $targetNodeName = isset($commandArguments['targetNodeName'])
            ? NodeName::fromString($commandArguments['targetNodeName'])
            : null;

        $command = CopyNodesRecursively::create(
            $subgraph,
            $currentNode,
            $targetDimensionSpacePoint,
            $initiatingUserIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['targetParentNodeAggregateIdentifier']),
            $targetSucceedingSiblingNodeAggregateIdentifier,
            $targetNodeName
        );

        $this->lastCommandOrEventResult = $this->getNodeDuplicationCommandHandler()
            ->handleCopyNodesRecursively($command);
    }
}
