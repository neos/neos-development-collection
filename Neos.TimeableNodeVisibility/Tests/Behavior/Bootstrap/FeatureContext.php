<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Neos\Behat\FlowBootstrapTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\TimeableNodeVisibility\Service\TimeableNodeVisibilityService;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
class FeatureContext implements Context
{
    use FlowBootstrapTrait;
    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;

    protected $isolated = false;


    private ContentRepository $contentRepository;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);

        $this->setupCRTestSuiteTrait();
    }

    /**
     * @When I handle exceeded node dates
     */
    public function iHandleExceededNodeDates(): void
    {
        $service = $this->getObject(TimeableNodeVisibilityService::class);
        $service->handleExceededNodeDates(
            $this->currentContentRepository->id,
            WorkspaceName::forLive()
        );
    }

    /**
     * @Then I expect this node to be enabled
     */
    public function iExpectThisNodeToBeEnabled(): void
    {
        Assert::assertNotNull($this->currentNode, 'No current node selected');
        $subgraph = $this->currentContentRepository->getContentGraph()->getSubgraph(
            $this->currentContentStreamId,
            $this->currentDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions(),
        );
        $currentNode = $subgraph->findNodeById($this->currentNode->nodeAggregateId);
        Assert::assertNotNull($currentNode, sprintf('Failed to find node with id "%s" in subgraph %s', $this->currentNode->nodeAggregateId->value, json_encode($subgraph)));
        Assert::assertFalse($currentNode->tags->contain(SubtreeTag::disabled()), sprintf('Node "%s" was expected to be enabled, but it is not', $this->currentNode->nodeAggregateId->value));
    }

    /**
     * @Then I expect this node to be disabled
     */
    public function iExpectThisNodeToBeDisabled(): void
    {
        Assert::assertNotNull($this->currentNode, 'No current node selected');
        $subgraph = $this->currentContentRepository->getContentGraph()->getSubgraph(
            $this->currentContentStreamId,
            $this->currentDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions(),
        );
        $currentNode = $subgraph->findNodeById($this->currentNode->nodeAggregateId);
        Assert::assertNotNull($currentNode, sprintf('Failed to find node with id "%s" in subgraph %s', $this->currentNode->nodeAggregateId->value, json_encode($subgraph)));
        Assert::assertTrue($currentNode->tags->contain(SubtreeTag::disabled()), sprintf('Node "%s" was expected to be disabled, but it is not', $this->currentNode->nodeAggregateId->value));
    }

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }

    protected function createContentRepository(
        ContentRepositoryId $contentRepositoryId
    ): ContentRepository {
        $this->contentRepositoryRegistry->resetFactoryInstance($contentRepositoryId);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();

        return $contentRepository;
    }
}
