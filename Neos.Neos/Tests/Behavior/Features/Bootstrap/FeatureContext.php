<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Neos\Behat\FlowBootstrapTrait;
use Neos\Behat\FlowEntitiesTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Utility\Environment;

class FeatureContext implements BehatContext
{
    use FlowBootstrapTrait;
    use FlowEntitiesTrait;
    use BrowserTrait;

    use CRTestSuiteTrait;
    use CRBehavioralTestsSubjectProvider;
    use RoutingTrait;
    use MigrationsTrait;
    use FusionTrait;

    protected Environment $environment;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->environment = $this->getObject(Environment::class);
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);

        $this->setupCRTestSuiteTrait(true);
    }

    /**
     * @BeforeScenario
     */
    public function resetContentRepositoryComponents(BeforeScenarioScope $scope): void
    {
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();
    }

    /**
     * @BeforeScenario
     */
    public function resetPersistenceManagerAndFeedbackCollection()
    {
        // FIXME: we have some strange race condition between the scenarios; my theory is that
        // somehow projectors still run in the background when we start from scratch...
        sleep(2);
        $this->getObject(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->clearState();
        // FIXME: FeedbackCollection is a really ugly, hacky SINGLETON; so it needs to be RESET!
        $this->getObject(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        // The UserService has a runtime cache - which we need to reset as well as our users get new IDs.
        // Did I already mention I LOVE in memory caches? ;-) ;-) ;-)
        $userService = $this->getObject(\Neos\Neos\Domain\Service\UserService::class);
        \Neos\Utility\ObjectAccess::setProperty($userService, 'runtimeUserCache', [], true);
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
