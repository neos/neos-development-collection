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

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceService;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentService;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentServiceFactory;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for "Structure Adjustments" related concerns
 */
trait StructureAdjustmentsTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @When /^I adjust the node structure for node type "([^"]*)"$/
     * @throws NodeTypeNotFound
     */
    public function iAdjustTheNodeStructureForNodeType(string $nodeTypeName): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $errors = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        foreach ($errors as $error) {
            $structureAdjustmentService->fixError($error);
        }
    }

    /**
     * @Then I expect no needed structure adjustments for type :nodeTypeName
     * @throws NodeTypeNotFound
     */
    public function iExpectNoStructureAdjustmentsForType(string $nodeTypeName): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $errors = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $errors = iterator_to_array($errors);
        Assert::assertEmpty($errors, implode(', ', array_map(fn (StructureAdjustment $adjustment) => $adjustment->render(), $errors)));
    }

    /**
     * @Then /^I expect the following structure adjustments for type "([^"]*)":$/
     * @throws NodeTypeNotFound
     */
    public function iExpectTheFollowingStructureAdjustmentsForType(string $nodeTypeName, TableNode $expectedAdjustments): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $actualAdjustments = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $actualAdjustments = iterator_to_array($actualAdjustments);

        $this->assertEqualStructureAdjustments($expectedAdjustments, $actualAdjustments);
    }

    protected function assertEqualStructureAdjustments(TableNode $expectedAdjustments, array $actualAdjustments): void
    {
        Assert::assertCount(count($expectedAdjustments->getHash()), $actualAdjustments, 'Number of adjustments must match.');

        foreach ($expectedAdjustments->getHash() as $i => $row) {
            if (!isset($row['Type']) || !isset($row['nodeAggregateId'])) {
                Assert::fail('Type and nodeAggregateId must be specified in assertion!');
            }
            $adjustment = $this->findAdjustmentsBasedOnTypeAndNodeAggregateIdAndDimensionSpacePoint($actualAdjustments, $row['Type'], $row['nodeAggregateId'], $row['dimensionSpacePoint'] ?? null);
            foreach ($row as $k => $v) {
                if (in_array($k, ['Type', 'nodeAggregateId', 'dimensionSpacePoint'])) {
                    continue;
                }

                Assert::assertEquals($v, $adjustment->getArguments()[$k], '"' . $k . '" did not match in line ' . $i);
            }
        }
    }

    private function findAdjustmentsBasedOnTypeAndNodeAggregateIdAndDimensionSpacePoint(
        array $actualAdjustments,
        string $type,
        string $nodeAggregateId,
        ?string $dimensionSpacePointAsJSON
    ): StructureAdjustment {
        foreach ($actualAdjustments as $adjustment) {
            assert($adjustment instanceof StructureAdjustment);
            if (
                $adjustment->getType() === $type
                && $adjustment->getArguments()['nodeAggregateId'] === $nodeAggregateId
                && ($dimensionSpacePointAsJSON === null || $adjustment->getArguments()['dimensionSpacePoint'] === $dimensionSpacePointAsJSON)
            ) {
                return $adjustment;
            }
        }
        Assert::fail(
            'Adjustment not found for type "' . $type . '", node aggregate id "' . $nodeAggregateId . '"'
                ($dimensionSpacePointAsJSON ? ' and dimension space point "' . $dimensionSpacePointAsJSON . '"' : '')
        );
    }

    /**
     * @When outdated workspaces are rebased
     */
    public function outdatedWorkspacesAreRebased(): void
    {
        /** @var WorkspaceMaintenanceService $workspaceMaintenanceService */
        $workspaceMaintenanceService = $this->getContentRepositoryService(new WorkspaceMaintenanceServiceFactory());
        $workspaceMaintenanceService->rebaseOutdatedWorkspaces();
    }

    /**
     * @When outdated workspaces are rebased with strategy :strategy
     */
    public function outdatedWorkspacesAreRebasedWithStrategy(string $strategy): void
    {
        /** @var WorkspaceMaintenanceService $workspaceMaintenanceService */
        $workspaceMaintenanceService = $this->getContentRepositoryService(new WorkspaceMaintenanceServiceFactory());
        $workspaceMaintenanceService->rebaseOutdatedWorkspaces(RebaseErrorHandlingStrategy::from($strategy));
    }
}
