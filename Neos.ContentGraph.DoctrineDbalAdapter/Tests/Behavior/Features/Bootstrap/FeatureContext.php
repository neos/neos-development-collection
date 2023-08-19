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

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Security/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/ProjectionIntegrityViolationDetectionTrait.php');

use Behat\Behat\Context\Context as BehatContext;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\ContentRepository\BehavioralTests\Tests\Functional\BehatTestHelper;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteTrait;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;

/**
 * Features context
 */
class FeatureContext implements BehatContext
{
    use FlowContextTrait;
    use IsolatedBehatStepsTrait;
    use ProjectionIntegrityViolationDetectionTrait;
    use CRTestSuiteTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->setupEventSourcedTrait();
        $this->setupDbalGraphAdapterIntegrityViolationTrait();
        $this->contentRepositoryRegistry = $this->objectManager->get(ContentRepositoryRegistry::class);
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    protected function getContentRepositoryService(
        ContentRepositoryServiceFactoryInterface $factory
    ): ContentRepositoryServiceInterface {
        return $this->contentRepositoryRegistry->buildService(
            $this->currentContentRepository->id,
            $factory
        );
    }

    protected function getContentRepository(ContentRepositoryId $id): ContentRepository
    {
        return $this->contentRepositoryRegistry->get($id);
    }
}
