<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Helper\ContentSubgraphs;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test projected nodes
 */
trait CurrentSubgraphTrait
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function getActiveContentGraphs(): ContentGraphs;


    /**
     * @Given /^I am in the active content stream of workspace "([^"]*)" and dimension space point (.*)$/
     * @throws \Exception
     */
    public function iAmInTheActiveContentStreamOfWorkspaceAndDimensionSpacePoint(string $workspaceName, string $dimensionSpacePoint): void
    {
        $workspaceName = WorkspaceName::fromString($workspaceName);
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new \Exception(sprintf('Workspace "%s" does not exist, projection not yet up to date?', $workspaceName->value), 1548149355);
        }
        $this->currentContentStreamId = $workspace->currentContentStreamId;
        $this->currentDimensionSpacePoint = DimensionSpacePoint::fromJsonString($dimensionSpacePoint);
    }

    /**
     * @Then /^I expect the subgraph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheSubgraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes)
    {
        foreach ($this->getCurrentSubgraphs() as $adapterName => $subgraph) {
            $actualNumberOfNodes = $subgraph->countNodes();
            Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content subgraph in adapter "' . $adapterName . '" consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
        }
    }

    protected function getCurrentSubgraphs(): ContentSubgraphs
    {
        $currentSubgraphs = [];
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            assert($contentGraph instanceof ContentGraphInterface);
            $currentSubgraphs[$adapterName] = $contentGraph->getSubgraph(
                $this->currentContentStreamId,
                $this->currentDimensionSpacePoint,
                $this->currentVisibilityConstraints
            );
        }

        return new ContentSubgraphs($currentSubgraphs);
    }
}
