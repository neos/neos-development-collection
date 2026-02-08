<?php

declare(strict_types=1);

use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Neos\Behat\FlowBootstrapTrait;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * Features context
 */
class FeatureContext implements BehatContext
{
    use FlowBootstrapTrait;
    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;
    use BulkNodeOperations;
    use BenchmarkSampling;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        self::bootstrapFlow();

        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);
    }

    /**
     * @BeforeScenario
     */
    public function resetContentRepositoryComponents(BeforeScenarioScope $scope): void
    {
        FakeContentDimensionSourceFactory::reset();
        FakeNodeTypeManagerFactory::reset();
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
        FakeContentDimensionSourceFactory::reset();
        FakeNodeTypeManagerFactory::reset();

        return $contentRepository;
    }
}
