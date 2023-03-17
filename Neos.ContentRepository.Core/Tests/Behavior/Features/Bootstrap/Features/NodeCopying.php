<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\NodesByAdapter;

/**
 * The node copying trait for behavioral tests
 */
trait NodeCopying
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function getCurrentContentStreamId(): ?ContentStreamId;

    abstract protected function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint;

    abstract protected function getAvailableContentGraphs(): ContentGraphs;

    abstract protected function getCurrentNodes(): ?NodesByAdapter;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When /^the command CopyNodesRecursively is executed, copying the current node aggregate with payload:$/
     */
    public function theCommandCopyNodesRecursivelyIsExecutedCopyingTheCurrentNodeAggregateWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentGraphs = $this->getAvailableContentGraphs()->getIterator()->getArrayCopy();
        $contentGraph = reset($contentGraphs);
        assert($contentGraph instanceof ContentGraphInterface);
        $subgraph = $contentGraph->getSubgraph(
            $this->getCurrentContentStreamId(),
            $this->getCurrentDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $currentNodes = $this->getCurrentNodes()->getIterator()->getArrayCopy();
        $currentNode = reset($currentNodes);
        $targetDimensionSpacePoint = isset($commandArguments['targetDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->getCurrentDimensionSpacePoint());
        $targetSucceedingSiblingNodeAggregateId = isset($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            : null;
        $targetNodeName = isset($commandArguments['targetNodeName'])
            ? NodeName::fromString($commandArguments['targetNodeName'])
            : null;

        $command = CopyNodesRecursively::createFromSubgraphAndStartNode(
            $subgraph,
            $currentNode,
            $targetDimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['targetParentNodeAggregateId']),
            $targetSucceedingSiblingNodeAggregateId,
            $targetNodeName
        );
        $command = $command->withNodeAggregateIdMapping(NodeAggregateIdMapping::fromArray($commandArguments['nodeAggregateIdMapping']));

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }
}
