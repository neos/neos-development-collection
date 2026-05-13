<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\DBAL\Connection;
use Neos\Behat\FlowBootstrapTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
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
    use DoctrineDbalProjectionIntegrityViolatorTrait;
    use DoctrineDbalProjectionSpyTrait;
    use ContentStreamLayerTrait;
    use CRTestSuiteTrait;
    use CRTestSuiteRuntimeVariables;
    use CRBehavioralTestsSubjectProvider;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected Connection $dbal;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);
        $this->dbal = $this->getObject(Connection::class);
    }

    private function tableNames(): ContentGraphTableNames
    {
        return ContentGraphTableNames::create(
            $this->currentContentRepository->id
        );
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
