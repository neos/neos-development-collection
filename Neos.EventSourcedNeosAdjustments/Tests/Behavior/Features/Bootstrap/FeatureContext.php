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
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');
require_once(__DIR__ . '/../../../../../Neos.ContentRepository.Intermediary/Tests/Behavior/Features/Bootstrap/IntermediaryCommandTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/BrowserTrait.php');
require_once(__DIR__ . '/FlowSubcommandTrait.php');
require_once(__DIR__ . '/FlowQueryTrait.php');
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Utility\Environment;

/**
 * Features context
 */
class FeatureContext implements \Behat\Behat\Context\Context
{
    use \Neos\Behat\Tests\Behat\FlowContextTrait;
    use FlowSubcommandTrait;
    use BrowserTrait;
    use EventSourcedTrait;
    use NodeOperationsTrait;
    use FlowQueryTrait;
    use IsolatedBehatStepsTrait;
    use RoutingTrait;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();

        $this->setupFlowSubcommandTrait();
        $this->setupEventSourcedTrait();
        $this->readModelFactory = $this->objectManager->get(\Neos\ContentRepository\Intermediary\Domain\ReadModelFactory::class);
    }

    /**
     * @BeforeScenario
     */
    public function resetPersistenceManagerAndFeedbackCollection()
    {
        // FIXME: we have some strange race condition between the scenarios; my theory is that
        // somehow projectors still run in the background when we start from scratch...
        sleep(2);
        $this->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->clearState();
        // FIXME: FeedbackCollection is a really ugly, hacky SINGLETON; so it needs to be RESET!
        $this->getObjectManager()->get(\Neos\Neos\Ui\Domain\Model\FeedbackCollection::class)->reset();

        // The UserService has a runtime cache - which we need to reset as well as our users get new IDs.
        // Did I already mention I LOVE in memory caches? ;-) ;-) ;-)
        $userService = $this->getObjectManager()->get(\Neos\Neos\Domain\Service\UserService::class);
        \Neos\Utility\ObjectAccess::setProperty($userService, 'runtimeUserCache', [], true);
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @return Environment
     */
    protected function getEnvironment()
    {
        return $this->objectManager->get(Environment::class);
    }
}
