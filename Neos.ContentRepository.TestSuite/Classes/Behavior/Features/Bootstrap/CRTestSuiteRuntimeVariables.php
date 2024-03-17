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

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeClock;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;

/**
 * The node creation trait for behavioral tests
 */
trait CRTestSuiteRuntimeVariables
{
    protected ?ContentRepository $currentContentRepository = null;

    protected ?ContentStreamId $currentContentStreamId = null;

    protected ?WorkspaceName $currentWorkspaceName = null;

    protected ?DimensionSpacePoint $currentDimensionSpacePoint = null;

    protected ?VisibilityConstraints $currentVisibilityConstraints = null;

    protected ?NodeAggregateId $currentRootNodeAggregateId = null;

    protected ?CommandResult $lastCommandOrEventResult = null;

    protected ?\Exception $lastCommandException = null;

    protected ?Node $currentNode = null;

    protected ?NodeAggregate $currentNodeAggregate = null;

    /**
     * @var array<string,NodeAggregateId>
     */
    protected array $rememberedNodeAggregateIds = [];

    /**
     * @Given /^I am in content repository "([^"]*)"$/
     */
    public function iAmInContentRepository(string $contentRepositoryId): void
    {
        $this->currentContentRepository = $this->getContentRepository(ContentRepositoryId::fromString($contentRepositoryId));
    }

    /**
     * @throws \DomainException if the requested content repository instance does not exist
     */
    abstract protected function getContentRepository(ContentRepositoryId $id): ContentRepository;

    /**
     * @Given /^I am user identified by "([^"]*)"$/
     */
    public function iAmUserIdentifiedBy(string $userId): void
    {
        FakeUserIdProvider::setUserId(UserId::fromString($userId));
    }

    /**
     * @When the current date and time is :timestamp
     */
    public function theCurrentDateAndTimeIs(string $timestamp): void
    {
        FakeClock::setNow(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestamp));
    }

    /**
     * @Given /^I am in content stream "([^"]*)"$/
     */
    public function iAmInContentStream(string $contentStreamId): void
    {
        $this->currentContentStreamId = ContentStreamId::fromString($contentStreamId);
    }

    /**
     * @Given /^I am in workspace "([^"]*)"$/
     */
    public function iAmInWorkspace(string $workspaceName): void
    {
        $this->currentWorkspaceName = WorkspaceName::fromString($workspaceName);
    }

    /**
     * @Given /^I am in the active content stream of workspace "([^"]*)"$/
     * @throws \Exception
     */
    public function iAmInTheActiveContentStreamOfWorkspace(string $workspaceName): void
    {
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspaceName));
        if ($workspace === null) {
            throw new \Exception(sprintf('Workspace "%s" does not exist, projection not yet up to date?', $workspaceName), 1548149355);
        }
        $this->currentWorkspaceName = WorkspaceName::fromString($workspaceName);
        $this->currentContentStreamId = $workspace->currentContentStreamId;
    }

    /**
     * @Given /^I am in dimension space point (.*)$/
     */
    public function iAmInDimensionSpacePoint(string $dimensionSpacePoint): void
    {
        $this->currentDimensionSpacePoint = DimensionSpacePoint::fromJsonString($dimensionSpacePoint);
    }

    /**
     * @Given /^I am in content stream "([^"]*)" and dimension space point (.*)$/
     */
    public function iAmInContentStreamAndDimensionSpacePoint(string $contentStreamId, string $dimensionSpacePoint): void
    {
        $this->iAmInContentStream($contentStreamId);
        $this->iAmInDimensionSpacePoint($dimensionSpacePoint);
    }

    /**
     * @Given /^I am in the active content stream of workspace "([^"]*)" and dimension space point (.*)$/
     * @throws \Exception
     */
    public function iAmInTheActiveContentStreamOfWorkspaceAndDimensionSpacePoint(string $workspaceName, string $dimensionSpacePoint): void
    {
        $this->iAmInTheActiveContentStreamOfWorkspace($workspaceName);
        $this->iAmInDimensionSpacePoint($dimensionSpacePoint);
    }

    /**
     * @When /^VisibilityConstraints are set to "(withoutRestrictions|frontend)"$/
     */
    public function visibilityConstraintsAreSetTo(string $restrictionType): void
    {
        $this->currentVisibilityConstraints = match ($restrictionType) {
            'withoutRestrictions' => VisibilityConstraints::withoutRestrictions(),
            'frontend' => VisibilityConstraints::frontend(),
            default => throw new \InvalidArgumentException('Visibility constraint "' . $restrictionType . '" not supported.'),
        };
    }

    public function getCurrentSubgraph(): ContentSubgraphInterface
    {
        return $this->currentContentRepository->getContentGraph()->getSubgraph(
            $this->currentContentStreamId,
            $this->currentDimensionSpacePoint,
            $this->currentVisibilityConstraints
        );
    }

    /**
     * @Given /^I remember NodeAggregateId of node "([^"]*)"s child "([^"]*)" as "([^"]*)"$/
     */
    public function iRememberNodeAggregateIdOfNodesChildAs(string $parentNodeAggregateId, string $childNodeName, string $indexName): void
    {
        $this->rememberedNodeAggregateIds[$indexName] = $this->getCurrentSubgraph()->findNodeByPath(
            NodePath::fromString($childNodeName),
            NodeAggregateId::fromString($parentNodeAggregateId),
        )->nodeAggregateId;
    }

    protected function getCurrentNodeAggregateId(): NodeAggregateId
    {
        assert($this->currentNode instanceof Node);
        return $this->currentNode->nodeAggregateId;
    }
}
