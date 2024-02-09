<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Neos\Behat\FlowBootstrapTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\TimeableNodeVisibility\Service\TimeableNodeVisibilityService;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Projection\NodeHiddenState\NodeHiddenStateFinder;
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
     * @Then I expect this node to be :state
     */
    public function iExpectThisNodeToBeState(string $state): void
    {
        $hiddenState = $this->currentContentRepository->projectionState(NodeHiddenStateFinder::class)
            ->findHiddenState(
                $this->currentContentStreamId,
                $this->currentDimensionSpacePoint,
                $this->currentNode->nodeAggregateId
            );
        if ($hiddenState->isHidden === false && $state != "enabled"
            ||
            $hiddenState->isHidden === true && $state != "disabled"
        ) {
            Assert::fail('Node has not the expected state');
        }

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
