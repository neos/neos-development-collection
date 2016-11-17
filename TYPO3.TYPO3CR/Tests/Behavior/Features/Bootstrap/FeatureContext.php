<?php

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/NodeOperationsTrait.php');
require_once(__DIR__ . '/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Flowpack\Behat\Tests\Behat\FlowContext;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use TYPO3\Flow\Utility\Environment;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Service\AuthorizationService;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap\NodeAuthorizationTrait;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;

/**
 * Features context
 */
class FeatureContext extends \Behat\Behat\Context\BehatContext
{
    use NodeOperationsTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = \TYPO3\TYPO3CR\Tests\Functional\Command\BehatTestHelper::class;

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new FlowContext($parameters));
        /** @var FlowContext $flowContext */
        $flowContext = $this->getSubcontext('flow');
        $this->objectManager = $flowContext->getObjectManager();
        $this->environment = $this->objectManager->get(Environment::class);
        $this->nodeAuthorizationService = $this->objectManager->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->setupSecurity();
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
