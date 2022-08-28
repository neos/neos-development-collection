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

namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\ContentGraphs;
use Neos\ContentRepository\Tests\Behavior\Features\Helper\ContentSubgraphs;
use PHPUnit\Framework\Assert;

/**
 * The feature trait to test projected nodes
 */
trait CurrentSubgraphTrait
{
    protected ?ContentStreamIdentifier $contentStreamIdentifier = null;

    protected ?DimensionSpacePoint $dimensionSpacePoint = null;

    protected ?VisibilityConstraints $visibilityConstraints = null;

    abstract protected function getActiveContentGraphs(): ContentGraphs;

    abstract protected function getWorkspaceFinder(): WorkspaceFinder;

    /**
     * @Given /^I am in content stream "([^"]*)"$/
     * @param string $contentStreamIdentifier
     */
    public function iAmInContentStream(string $contentStreamIdentifier): void
    {
        $this->contentStreamIdentifier = ContentStreamIdentifier::fromString($contentStreamIdentifier);
    }
    /**
     * @Given /^I am in dimension space point (.*)$/
     * @param string $dimensionSpacePoint
     */
    public function iAmInDimensionSpacePoint(string $dimensionSpacePoint): void
    {
        $this->dimensionSpacePoint = DimensionSpacePoint::fromJsonString($dimensionSpacePoint);
    }

    /**
     * @Given /^I am in content stream "([^"]*)" and dimension space point (.*)$/
     * @param string $contentStreamIdentifier
     * @param string $dimensionSpacePoint
     */
    public function iAmInContentStreamAndDimensionSpacePoint(string $contentStreamIdentifier, string $dimensionSpacePoint)
    {
        $this->iAmInContentStream($contentStreamIdentifier);
        $this->iAmInDimensionSpacePoint($dimensionSpacePoint);
    }

    /**
     * @Given /^I am in the active content stream of workspace "([^"]*)" and dimension space point (.*)$/
     * @throws \Exception
     */
    public function iAmInTheActiveContentStreamOfWorkspaceAndDimensionSpacePoint(string $workspaceName, string $dimensionSpacePoint): void
    {
        $workspaceName = WorkspaceName::fromString($workspaceName);
        $workspace = $this->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new \Exception(sprintf('Workspace "%s" does not exist, projection not yet up to date?', $workspaceName), 1548149355);
        }
        $this->contentStreamIdentifier = $workspace->currentContentStreamIdentifier;
        $this->dimensionSpacePoint = DimensionSpacePoint::fromJsonString($dimensionSpacePoint);
    }

    /**
     * @When /^VisibilityConstraints are set to "(withoutRestrictions|frontend)"$/
     * @param string $restrictionType
     */
    public function visibilityConstraintsAreSetTo(string $restrictionType)
    {
        $this->visibilityConstraints = match ($restrictionType) {
            'withoutRestrictions' => VisibilityConstraints::withoutRestrictions(),
            'frontend' => VisibilityConstraints::frontend(),
            default => throw new \InvalidArgumentException('Visibility constraint "' . $restrictionType . '" not supported.'),
        };
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

    public function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getCurrentDimensionSpacePoint(): ?DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getCurrentVisibilityConstraints(): ?VisibilityConstraints
    {
        return $this->visibilityConstraints;
    }

    protected function getCurrentSubgraphs(): ContentSubgraphs
    {
        $currentSubgraphs = [];
        foreach ($this->getActiveContentGraphs() as $adapterName => $contentGraph) {
            assert($contentGraph instanceof ContentGraphInterface);
            $currentSubgraphs[$adapterName] = $contentGraph->getSubgraph(
                $this->contentStreamIdentifier,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints
            );
        }

        return new ContentSubgraphs($currentSubgraphs);
    }
}
