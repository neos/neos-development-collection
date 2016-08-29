<?php
namespace TYPO3\Neos\Tests\Functional\Command;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');
if (file_exists(FLOW_PATH_PACKAGES . '/Neos')) {
    require_once(FLOW_PATH_PACKAGES . '/Neos/TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
    require_once(FLOW_PATH_PACKAGES . '/Neos/TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
} else {
    require_once(FLOW_PATH_PACKAGES . '/Application/TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
    require_once(FLOW_PATH_PACKAGES . '/Application/TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
}

use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap\NodeAuthorizationTrait;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * A test helper, to include behat step traits, beeing executed by
 * the BehatHelperCommandController.
 *
 * @Flow\Scope("singleton")
 */
class BehatTestHelper
{
    use IsolatedBehatStepsTrait;
    use SecurityOperationsTrait;
    use NodeOperationsTrait;
    use NodeAuthorizationTrait;

    /**
     * @var Bootstrap
     */
    protected static $bootstrap;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @return void
     */
    public function initializeObject()
    {
        self::$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
        $this->isolated = false;
    }

    /**
     * @return mixed
     */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }
}
