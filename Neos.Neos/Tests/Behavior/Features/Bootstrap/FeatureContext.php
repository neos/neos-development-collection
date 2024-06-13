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
use Neos\Behat\FlowBootstrapTrait;
use Neos\Behat\FlowEntitiesTrait;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinPyStringNodeBasedNodeTypeManagerFactory;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\GherkinTableNodeBasedContentDimensionSourceFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Utility\Environment;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\Flow\Persistence\PersistenceManagerInterface;

class FeatureContext implements BehatContext
{
    use FlowBootstrapTrait;
    use FlowEntitiesTrait;
    use BrowserTrait;

    use CRTestSuiteTrait {
        deserializeProperties  as deserializePropertiesCrTestSuiteTrait;
    }
    use CRBehavioralTestsSubjectProvider;
    use RoutingTrait;
    use MigrationsTrait;
    use FusionTrait;

    use ContentCacheTrait;
    use AssetTrait;

    protected Environment $environment;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;
    protected PersistenceManagerInterface $persistenceManager;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->environment = $this->getObject(Environment::class);
        $this->contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);
        $this->persistenceManager = $this->getObject(PersistenceManagerInterface::class);

    }

    /*
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *  Please don't add any generic step definitions here and use   *
     *  a dedicated trait instead to keep this main class tidied up. *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    /**
     * @BeforeScenario
     */
    public function resetContentRepositoryComponents(): void
    {
        GherkinTableNodeBasedContentDimensionSourceFactory::reset();
        GherkinPyStringNodeBasedNodeTypeManagerFactory::reset();
    }

    /**
     * @BeforeScenario
     */
    public function resetPersistenceManagerAndFeedbackCollection()
    {
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

    protected function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        $properties = array_map(
            $this->loadObjectsRecursive(...),
            $properties
        );

        return $this->deserializePropertiesCrTestSuiteTrait($properties);
    }

    private function loadObjectsRecursive(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, 'Asset:')) {
            $assetIdentier = substr($value, strlen('Asset:'));
            return $this->persistenceManager->getObjectByIdentifier($assetIdentier, 'Neos\\Media\\Domain\\Model\\Asset', true);
        } elseif (is_array($value)) {
            return array_map(
                $this->loadObjectsRecursive(...),
                $value
            );
        }
        return $value;
    }
}
