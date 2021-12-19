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
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/ContentStreamForking.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeCopying.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeCreation.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeDisabling.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeModification.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeMove.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeProperties.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeReferencing.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeRemoval.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeRenaming.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeTypeChange.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/NodeVariation.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/WorkspaceCreation.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/WorkspaceDiscarding.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/Features/WorkspacePublishing.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/CurrentSubgraphTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/CurrentUserTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/GenericCommandExecutionAndEventPublication.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ProjectedNodeAggregateTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ProjectedNodeTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/MigrationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/StructureAdjustmentsTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/ReadModelInstantiationTrait.php');
require_once(__DIR__ . '/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/../../../../../Neos.EventSourcedContentRepository/Tests/Behavior/Features/Bootstrap/EventSourcedTrait.php');

use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentGraph\DoctrineDbalAdapter\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait as BaseDetectionTrait;
use Neos\EventSourcedContentRepository\Tests\Functional\Command\BehatTestHelper;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Utility\Environment;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;

/**
 * Features context
 */
class FeatureContext implements \Behat\Behat\Context\Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use IsolatedBehatStepsTrait;
    use ProjectionIntegrityViolationDetectionTrait;
    use BaseDetectionTrait;
    use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\EventSourcedTrait;

    protected string $behatTestHelperObjectName = BehatTestHelper::class;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->setupEventSourcedTrait();
        $this->setupProjectionIntegrityViolationDetectionTrait();
        $this->setupDbalGraphAdapterIntegrityViolationTrait();
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
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
