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
require_once(__DIR__ . '/NodeOperationsTrait.php');
require_once(__DIR__ . '/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/ProjectionIntegrityViolationDetectionTrait.php');
require_once(__DIR__ . '/StructureAdjustmentsTrait.php');
require_once(__DIR__ . '/MigrationsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\MigrationsTrait;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\ProjectionIntegrityViolationDetectionTrait;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\StructureAdjustmentsTrait;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Utility\Environment;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\NodeAuthorizationTrait;
use Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;

/**
 * Features context
 */
class FeatureContext implements \Behat\Behat\Context\Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;
    use EventSourcedTrait;
    use ProjectionIntegrityViolationDetectionTrait;
    use StructureAdjustmentsTrait;
    use MigrationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = \Neos\EventSourcedContentRepository\Tests\Functional\Command\BehatTestHelper::class;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();

        $this->setupSecurity();
        $this->setupEventSourcedTrait();
        $this->setupIntegrityViolationTrait();
        $this->setupProjectionIntegrityViolationDetectionTrait();
        $this->setupMigrationsTrait();
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
