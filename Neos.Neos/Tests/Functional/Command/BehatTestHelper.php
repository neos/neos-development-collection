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

require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Annotations as Flow;

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
    // TODO use LegacyNodeOperationsTrait;
    // TODO use NodeAuthorizationTrait;

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
