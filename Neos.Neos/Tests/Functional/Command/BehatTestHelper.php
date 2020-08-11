<?php
namespace Neos\Neos\Tests\Functional\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(FLOW_PATH_PACKAGES . '/Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');
if (file_exists(FLOW_PATH_PACKAGES . '/Neos')) {
    require_once(FLOW_PATH_PACKAGES . '/Neos/Neos.ContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
    require_once(FLOW_PATH_PACKAGES . '/Neos/Neos.ContentRepository/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
} else {
    require_once(FLOW_PATH_PACKAGES . '/Application/Neos.ContentRepository/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
    require_once(FLOW_PATH_PACKAGES . '/Application/Neos.ContentRepository/Tests/Behavior/Features/Bootstrap/NodeAuthorizationTrait.php');
}

use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeAuthorizationTrait;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

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
        self::$bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
        $this->isolated = false;
    }

    /**
     * @return mixed
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }
}
