<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once('ReadModelInstantiationTrait.php');

use Behat\Behat\Context\Context as BehatContext;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\Intermediary\Tests\Behavior\Features\Bootstrap\ReadModelInstantiationTrait;
use Neos\EventSourcedContentRepository\Tests\Functional\Command\BehatTestHelper;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Utility\Environment;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;

/**
 * Features context
 */
class FeatureContext implements BehatContext
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use IsolatedBehatStepsTrait;
    use EventSourcedTrait;
    use ReadModelInstantiationTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->setupEventSourcedTrait();
        $this->setupReadModeInstantiationTrait();
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
