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

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeClock;
use Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GherkinTableNodeBasedContentDimensionSource;
use Symfony\Component\Yaml\Yaml;

/**
 * The node creation trait for behavioral tests
 */
trait CRTestSuiteRuntimeVariables
{
    protected ?ContentDimensionSourceInterface $contentDimensionsToUse = null;

    /**
     * @var array<string,ContentRepository>
     */
    protected array $contentRepositories = [];

    /**
     * A runtime cache of all content repositories already set up, represented by their ID
     * @var array<ContentRepositoryId>
     */
    protected array $alreadySetUpContentRepositories = [];

    protected ?ContentRepository $currentContentRepository = null;

    protected ?ContentStreamId $currentContentStreamId = null;

    protected ?DimensionSpacePoint $currentDimensionSpacePoint = null;

    protected ?VisibilityConstraints $currentVisibilityConstraints = null;

    protected ?NodeAggregateId $currentRootNodeAggregateId = null;

    protected ?CommandResult $lastCommandOrEventResult = null;

    protected ?\Exception $lastCommandException = null;

    /**
     * @Given /^I am in content repository "([^"]*)"
     */
    public function iAmInContentRepository(string $contentRepositoryId): void
    {
        if (array_key_exists($contentRepositoryId, $this->contentRepositories)) {
            $this->currentContentRepository = $this->contentRepositories[$contentRepositoryId];
        } else {
            throw new \DomainException('undeclared content repository ' . $contentRepositoryId);
        }
    }

    /**
     * @Given /^I use no content dimensions$/
     */
    public function iUseNoContentDimensions(): void
    {
        $this->contentDimensionsToUse = GherkinTableNodeBasedContentDimensionSource::createEmpty();
    }


    /**
     * @Given /^I use the following content dimensions:$/
     */
    public function iUseTheFollowingContentDimensions(TableNode $contentDimensions): void
    {
        $this->contentDimensionsToUse = GherkinTableNodeBasedContentDimensionSource::fromGherkinTableNode($contentDimensions);
    }


    /**
     * @Given /^I use the following content dimensions to override content repository "([^"]*)":$/
     */
    public function iUseTheFollowingContentDimensionsToOverrideContentRepository(string $contentRepositoryId, TableNode $contentDimensions): void
    {
        if (!array_key_exists($contentRepositoryId, $this->contentRepositories)) {
            throw new \DomainException('undeclared content repository ' . $contentRepositoryId);
        } else {
            $this->contentRepositories[$contentRepositoryId] = $this->createContentRepository(
                ContentRepositoryId::fromString($contentRepositoryId),
                GherkinTableNodeBasedContentDimensionSource::fromGherkinTableNode($contentDimensions),
                $this->currentContentRepository->getNodeTypeManager()
            );
            $this->iAmInContentRepository($contentRepositoryId);
        }
    }

    /**
     * @Given /^the following NodeTypes to define content repository "([^"]*)":$/
     */
    public function theFollowingNodeTypesToDefineContentRepository(string $contentRepositoryId, PyStringNode $serializedNodeTypesConfiguration): void
    {
        $this->defineAndSelectContentRepository(
            $contentRepositoryId,
            $serializedNodeTypesConfiguration,
            null
        );
    }

    /**
     * @Given /^the following NodeTypes with fallback to "([^"]*)" to define content repository "([^"]*)":$/
     */
    public function theFollowingNodeTypesWithFallbackToDefineContentRepository(string $fallback, string $contentRepositoryId, PyStringNode $serializedNodeTypesConfiguration): void
    {
        $this->defineAndSelectContentRepository(
            $contentRepositoryId,
            $serializedNodeTypesConfiguration,
            $fallback
        );
    }

    private function defineAndSelectContentRepository(
        string $contentRepositoryId,
        PyStringNode $serializedNodeTypesConfiguration,
        ?string $fallbackNodeTypeName
    ): void {
        if (array_key_exists($contentRepositoryId, $this->contentRepositories)) {
            throw new \DomainException('already declared content repository ' . $contentRepositoryId);
        } else {
            $this->contentRepositories[$contentRepositoryId] = $this->setUpContentRepository(
                ContentRepositoryId::fromString($contentRepositoryId),
                $this->contentDimensionsToUse,
                $this->createNodeTypeManager(
                    $serializedNodeTypesConfiguration,
                    $fallbackNodeTypeName
                )
            );
            $this->iAmInContentRepository($contentRepositoryId);
        }
    }

    /**
     * @Given /^the following NodeTypes to override content repository "([^"]*)":$/
     */
    public function theFollowingNodeTypesToOverrideContentRepository(
        string $contentRepositoryId,
        PyStringNode $serializedNodeTypesConfiguration
    ): void {
        $this->overrideAndSelectContentRepository(
            $contentRepositoryId,
            $serializedNodeTypesConfiguration,
            null
        );
    }

    /**
     * @Given /^the following NodeTypes with fallback to "([^"]*)" to override content repository "([^"]*)":$/
     */
    public function theFollowingNodeTypesWithFallbackToOverrideContentRepository(
        string $fallbackNodeTypeName,
        string $contentRepositoryId,
        PyStringNode $serializedNodeTypesConfiguration
    ): void {
        $this->overrideAndSelectContentRepository(
            $contentRepositoryId,
            $serializedNodeTypesConfiguration,
            $fallbackNodeTypeName
        );
    }

    private function overrideAndSelectContentRepository(
        string $contentRepositoryId,
        PyStringNode $serializedNodeTypesConfiguration,
        ?string $fallbackNodeTypeName
    ): void {
        if (!array_key_exists($contentRepositoryId, $this->contentRepositories)) {
            throw new \DomainException('undeclared content repository ' . $contentRepositoryId);
        } else {
            $this->contentRepositories[$contentRepositoryId] = $this->createContentRepository(
                ContentRepositoryId::fromString($contentRepositoryId),
                $this->contentDimensionsToUse,
                $this->createNodeTypeManager(
                    $serializedNodeTypesConfiguration,
                    $fallbackNodeTypeName
                )
            );
            $this->iAmInContentRepository($contentRepositoryId);
        }
    }

    protected function setUpContentRepository(
        ContentRepositoryId $contentRepositoryId,
        ContentDimensionSourceInterface $contentDimensionSource,
        NodeTypeManager $nodeTypeManager
    ): ContentRepository {
        /**
         * Reset events and projections
         * ============================
         *
         * PITFALL: for a long time, the code below was a two-liner (it is not anymore, for reasons explained here):
         * - reset projections (truncate table contents)
         * - truncate events table.
         *
         * This code has SERIOUS Race Condition and Bug Potential.
         * tl;dr: It is CRUCIAL that *FIRST* the event store is emptied, and *then* the projection state is reset;
         * so the OPPOSITE order as described above.
         *
         * If doing it in the way described initially, the following can happen (time flows from top to bottom):
         *
         * ```
         * Main Behat Process                        Dangling Projection catch up worker
         * ==================                        ===================================
         *
         *                                           (hasn't started working yet, simply sleeping)
         *
         * 1) Projection State reset
         *                                           "oh, I have some work to do to catch up EVERYTHING"
         *                                           "query the events table"
         *
         * 2) Event Table Reset
         *                                           (events table is already loaded into memory) -> replay WIP
         *
         * (new commands/events start happening,
         * in the new testcase)
         *                                           ==> ERRORS because the projection now contains the result of both
         *                                               old AND new events (of the two different testcases) <==
         * ```
         *
         * This was an actual bug which bit us and made our tests unstable :D :D
         *
         * How did we find this? By the virtue of our Race Tracker (Docs: see {@see RaceTrackerCatchUpHook}), which
         * checks for events being applied multiple times to a projection.
         * ... and additionally by using {@see logToRaceConditionTracker()} to find the interleavings between the
         * Catch Up process and the testcase reset.
         */

        // @todo reset eventstore instead of doing database stuff here
        $eventTableName = sprintf('cr_%s_events', $contentRepositoryId->value);
        $this->getDatabaseConnection()->executeStatement('TRUNCATE ' . $eventTableName);

        $contentRepository = $this->createContentRepository(
            $contentRepositoryId,
            $contentDimensionSource,
            $nodeTypeManager
        );

        if (!in_array($contentRepository->id, $this->alreadySetUpContentRepositories)) {
            $contentRepository->setUp();
        }
        $contentRepository->resetProjectionStates();

        return $contentRepository;
    }

    abstract protected function getDatabaseConnection(): Connection;

    protected function createNodeTypeManager(PyStringNode $serializedNodeTypesConfiguration, ?string $fallbackNodeTypeName = null): NodeTypeManager
    {
        return new NodeTypeManager(
            fn (): array => Yaml::parse($serializedNodeTypesConfiguration->getRaw()),
            new class implements NodeLabelGeneratorFactoryInterface {
                public function create(NodeType $nodeType): NodeLabelGeneratorInterface
                {
                    return new class implements NodeLabelGeneratorInterface {
                        public function getLabel(Node $node): string
                        {
                            return $node->nodeType->getLabel();
                        }
                    };
                }
            },
            $fallbackNodeTypeName
        );
    }

    abstract protected function createContentRepository(
        ContentRepositoryId $contentRepositoryId,
        ContentDimensionSourceInterface $contentDimensionSource,
        NodeTypeManager $nodeTypeManager
    ): ContentRepository;

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
}
