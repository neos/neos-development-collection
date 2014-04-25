<?php

use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use PHPUnit_Framework_Assert as Assert;
use TYPO3\Flow\Utility\Arrays;

require_once(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends Behat\Behat\Context\BehatContext {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \Behat\Mink\Element\ElementInterface
	 */
	protected $selectedContentElement;

	/**
	 * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	protected $currentNodes = array();

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
		$this->objectManager = $this->getSubcontext('flow')->getObjectManager();
	}

	/**
	 * @BeforeScenario @fixtures
	 */
	public function resetNodeInstances() {
		$this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->reset();
		$this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->reset();
		$this->objectManager->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->reset();
	}

	/**
	 * @BeforeScenario @fixtures
	 */
	public function resetContentDimensions() {
		$contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
			// Set the content dimensions to a fixed value for behat scenarios
		$contentDimensionRepository->setDimensionsConfiguration(array('locales' => array('default' => 'mul_ZZ')));
	}

	/**
	 * @Given /^I have the following nodes:$/
	 * @When /^I create the following nodes:$/
	 */
	public function iHaveTheFollowingNodes(TableNode $table) {
		/** @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager $nodeTypeManager */
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');

		$rows = $table->getHash();
		foreach ($rows as $row) {
			$path = $row['Path'];
			$name = array_slice(explode('/', $path), -1)[0];
			$parentPath = implode('/', array_slice(explode('/', $path), 0, -1)) ? : '/';

			$context = $this->getContextForProperties($row, TRUE);

			if (isset($row['Node Type']) && $row['Node Type'] !== '') {
				$nodeType = $nodeTypeManager->getNodeType($row['Node Type']);
			} else {
				$nodeType = NULL;
			}

			if (isset($row['Identifier'])) {
				$identifier = $row['Identifier'];
			} else {
				$identifier = NULL;
			}

			$parentNode = $context->getNode($parentPath);
			if ($parentNode === NULL) {
				throw new Exception(sprintf('Could not get parent node with path %s to create node %s', $parentPath, $path));
			}

			$dimensions = NULL;
			// If Locales is set we pass them as explicit dimensions
			if (isset($row['Locales'])) {
				$dimensions['locales'] = explode(',', $row['Locales']);
			} elseif (isset($row['Locale'])) {
				$dimensions['locales'] = array($row['Locale']);
			}
			// Add flexible dimensions to explicit dimensions
			foreach ($row as $propertyName => $propertyValue) {
				if (strpos($propertyName, 'Dimension: ') === 0) {
					$dimensions[substr($propertyName, strlen('Dimension: '))] = Arrays::trimExplode(',', $propertyValue);
				}
			}

			$node = $parentNode->createNode($name, $nodeType, $identifier, $dimensions);

			if (isset($row['Properties']) && $row['Properties'] !== '') {
				$properties = json_decode($row['Properties'], TRUE);
				if ($properties === NULL) {
					throw new Exception(sprintf('Error decoding json value "%s": %d', $row['Properties'], json_last_error()));
				}
				foreach ($properties as $propertyName => $propertyValue) {
					$node->setProperty($propertyName, $propertyValue);
				}
			}
		}

		// Make sure we do not use cached instances
		$this->getSubcontext('flow')->persistAll();
		$this->resetNodeInstances();
	}

	/**
	 * @Given /^I have the following content dimensions:$/
	 */
	public function iHaveTheFollowingContentDimensions(TableNode $table) {
		$contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
		$dimensions = array();
		foreach ($table->getHash() as $row) {
			$dimensions[$row['Identifier']] = array(
				'default' => $row['Default']
			);
		}
		$contentDimensionRepository->setDimensionsConfiguration($dimensions);
	}

	/**
	 * @When /^I get a node by path "([^"]*)" with the following context:$/
	 */
	public function iGetANodeByPathWithTheFollowingContext($path, TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$node = $context->getNode($path);
		if ($node !== NULL) {
			$this->currentNodes = array($node);
		} else {
			$this->currentNodes = array();
		}
	}

	/**
	 * @When /^I get a node by identifier "([^"]*)" with the following context:$/
	 */
	public function iGetANodeByIdentifierWithTheFollowingContext($identifier, TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$node = $context->getNodeByIdentifier($identifier);
		if ($node !== NULL) {
			$this->currentNodes = array($node);
		} else {
			$this->currentNodes = array();
		}
	}

	/**
	 * @When /^I get the child nodes of "([^"]*)" with the following context:$/
	 */
	public function iGetTheChildNodesOfWithTheFollowingContext($path, TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$node = $context->getNode($path);

		$this->currentNodes = $node->getChildNodes();
	}

	/**
	 * @When /^I get the child nodes of "([^"]*)" with filter "([^"]*)" and the following context:$/
	 */
	public function iGetTheChildNodesOfWithFilterAndTheFollowingContext($path, $filter, TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$node = $context->getNode($path);

		$this->currentNodes = $node->getChildNodes($filter);
	}

	/**
	 * @When /^I get the nodes on path "([^"]*)" to "([^"]*)" with the following context:$/
	 */
	public function iGetTheNodesOnPathToWithTheFollowingContext($startingPoint, $endPoint, TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$this->currentNodes = $context->getNodesOnPath($startingPoint, $endPoint);
	}

	/**
	 * @When /^I publish the node$/
	 */
	public function iPublishNodeToWorkspaceWithTheFollowingContext() {
		$node = $this->iShouldHaveOneNode();

		/** @var \TYPO3\Neos\Service\PublishingService $publishingService */
		$publishingService = $this->objectManager->get('TYPO3\Neos\Service\PublishingService');
		$publishingService->publishNode($node);

		$this->getSubcontext('flow')->persistAll();
		$this->resetNodeInstances();
	}

	/**
	 * @When /^I publish the workspace "([^"]*)"$/
	 */
	public function iPublishTheWorkspace($sourceWorkspaceName) {
		$sourceContext = $this->getContextForProperties(array('Workspace' => $sourceWorkspaceName));
		$sourceWorkspace = $sourceContext->getWorkspace();

		$liveContext = $this->getContextForProperties(array('Workspace' => 'live'));
		$liveWorkspace = $liveContext->getWorkspace();

		$sourceWorkspace->publish($liveWorkspace);

		$this->getSubcontext('flow')->persistAll();
		$this->resetNodeInstances();
	}

	/**
	 * @Given /^I remove the node$/
	 */
	public function iRemoveTheNode() {
		$node = $this->iShouldHaveOneNode();
		$node->remove();

		$this->getSubcontext('flow')->persistAll();
		$this->resetNodeInstances();
	}

	/**
	 * @Then /^I should have one node$/
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function iShouldHaveOneNode() {
		Assert::assertCount(1, $this->currentNodes);
		return $this->currentNodes[0];
	}

	/**
	 * @Then /^I should have (\d+) nodes$/
	 */
	public function iShouldHaveNodes($count) {
		Assert::assertCount((integer)$count, $this->currentNodes);
	}

	/**
	 * @Then /^The node property "([^"]*)" should be "([^"]*)"$/
	 */
	public function theNodePropertyShouldBe($propertyName, $propertyValue) {
		$currentNode = $this->iShouldHaveOneNode();
		Assert::assertEquals($propertyValue, $currentNode->getProperty($propertyName));
	}

	/**
	 * @When /^I set the node property "([^"]*)" to "([^"]*)"$/
	 */
	public function iSetTheNodePropertyTo($propertyName, $propertyValue) {
		$currentNode = $this->iShouldHaveOneNode();
		$currentNode->setProperty($propertyName, $propertyValue);

		$this->getSubcontext('flow')->persistAll();
		$this->resetNodeInstances();
	}

	/**
	 * @Then /^The node locales dimension should be "([^"]*)"$/
	 */
	public function theNodeLocaleShouldBe($locales) {
		$currentNode = $this->iShouldHaveOneNode();
		$dimensions = $currentNode->getDimensions();
		Assert::assertEquals($locales, implode(',', $dimensions['locales']), 'Locale should match');
	}

	/**
	 * @Then /^I should have a node with path "([^"]*)" and value "([^"]*)" for property "([^"]*)" for the following context:$/
	 */
	public function iShouldHaveANodeWithPathAndValueForPropertyForTheFollowingContext($path, $propertyValue, $propertyName, TableNode $table) {
		$this->iGetANodeByPathWithTheFollowingContext($path, $table);
		$this->theNodePropertyShouldBe($propertyName, $propertyValue);
	}

	/**
	 * @When /^I adopt the node to the following context:$/
	 */
	public function iTransferNodeToTheFollowingContext(TableNode $table) {
		$rows = $table->getHash();
		$context = $this->getContextForProperties($rows[0]);

		$currentNode = $this->iShouldHaveOneNode();
		$this->currentNodes = array($context->adoptNode($currentNode));
	}

	/**
	 * @Then /^I should have the following nodes:$/
	 */
	public function iShouldHaveTheFollowingNodes(TableNode $table) {
		$rows = $table->getHash();

		Assert::assertCount(count($rows), $this->currentNodes, 'Current nodes should match count of examples');

		foreach ($rows as $index => $row) {
			if (isset($row['Path'])) {
				Assert::assertEquals($row['Path'], $this->currentNodes[$index]->getPath(), 'Path should match');
			}
			if (isset($row['Properties'])) {
				$nodeProperties = $this->currentNodes[$index]->getProperties();
				$testProperties = json_decode($row['Properties'], TRUE);
				foreach ($testProperties as $property => $value) {
					Assert::assertArrayHasKey($property, $nodeProperties, 'Expected property should exist');
					Assert::assertEquals($value, $nodeProperties[$property], 'The value for property "' . $property . '" should match the expected value');
				}
			}
			if (isset($row['Locales'])) {
				$dimensions = $this->currentNodes[$index]->getDimensions();
				Assert::assertEquals($row['Locales'], implode(',', $dimensions['locales']), 'Locale should match');
			}
		}
	}

	/**
	 * @param array $humanReadableContextProperties
	 * @param boolean $addDimensionDefaults
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function getContextForProperties(array $humanReadableContextProperties, $addDimensionDefaults = FALSE) {
		/** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
		$contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$contextProperties = array();
		if (isset($humanReadableContextProperties['Locale'])) {
			$contextProperties['dimensions']['locales'] = array($humanReadableContextProperties['Locale'], 'mul_ZZ');
		}
		if (isset($humanReadableContextProperties['Locales'])) {
			$contextProperties['dimensions']['locales'] = Arrays::trimExplode(',', $humanReadableContextProperties['Locales']);
		}
		if (isset($humanReadableContextProperties['Workspace'])) {
			$contextProperties['workspaceName'] = $humanReadableContextProperties['Workspace'];
		}
		foreach ($humanReadableContextProperties as $propertyName => $propertyValue) {
			// Set flexible dimensions from features
			if (strpos($propertyName, 'Dimension: ') === 0) {
				$contextProperties['dimensions'][substr($propertyName, strlen('Dimension: '))] = Arrays::trimExplode(',', $propertyValue);
			}

			if (strpos($propertyName, 'Target dimension: ') === 0) {
				$contextProperties['targetDimensions'][substr($propertyName, strlen('Target dimension: '))] = $propertyValue;
			}
		}

		if ($addDimensionDefaults) {
			$contentDimensionRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
			$availableDimensions = $contentDimensionRepository->findAll();
			foreach ($availableDimensions as $dimension) {
				if (isset($contextProperties['dimensions'][$dimension->getIdentifier()]) && !in_array($dimension->getDefault(), $contextProperties['dimensions'][$dimension->getIdentifier()])) {
					$contextProperties['dimensions'][$dimension->getIdentifier()][] = $dimension->getDefault();
				}
			}
		}

		return $contextFactory->create($contextProperties);
	}
}
