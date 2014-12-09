<?php

require_once(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/NodeOperationsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Flowpack\Behat\Tests\Behat\FlowContext;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Boostrap\NodeOperationsTrait;

/**
 * Features context
 */
class FeatureContext extends Behat\Behat\Context\BehatContext {

	use NodeOperationsTrait;

	use SecurityOperationsTrait;

	use IsolatedBehatStepsTrait;

	/**
	 * @var string
	 */
	protected $behatTestHelperObjectName = 'TYPO3\TYPO3CR\Tests\Functional\Command\BehatTestHelper';

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new FlowContext($parameters));
		$flowContext = $this->getSubcontext('flow');
		$this->objectManager = $flowContext->getObjectManager();
		$this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
	}

	protected function getObjectManager() {
		return $this->objectManager;
	}
}
