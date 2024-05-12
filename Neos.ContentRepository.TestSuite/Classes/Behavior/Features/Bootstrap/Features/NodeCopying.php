<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;

/**
 * The node copying trait for behavioral tests
 */
trait NodeCopying
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When /^the command CopyNodesRecursively is executed, copying the current node aggregate with payload:$/
     */
    public function theCommandCopyNodesRecursivelyIsExecutedCopyingTheCurrentNodeAggregateWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $subgraph = $this->currentContentRepository->getContentGraph($this->currentWorkspaceName)->getSubgraph(
            $this->currentDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $targetDimensionSpacePoint = isset($commandArguments['targetDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);
        $targetSucceedingSiblingNodeAggregateId = isset($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            : null;
        $targetNodeName = isset($commandArguments['targetNodeName'])
            ? NodeName::fromString($commandArguments['targetNodeName'])
            : null;
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;

        $command = CopyNodesRecursively::createFromSubgraphAndStartNode(
            $subgraph,
            $workspaceName,
            $this->currentNode,
            $targetDimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['targetParentNodeAggregateId']),
            $targetSucceedingSiblingNodeAggregateId,
            $targetNodeName
        );
        $command = $command->withNodeAggregateIdMapping(NodeAggregateIdMapping::fromArray($commandArguments['nodeAggregateIdMapping']));

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }
}
