<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use TYPO3\Flow\Utility\Arrays;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TYPO3CR\Service\PublishingServiceInterface;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires that the Flow Object Manager must be available via $this->getObjectManager().
 *
 * Note: This trait expects the IsolatedBehatStepsTrait to be available!
 */
trait NodeOperationsTrait {

	/**
	 * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	private $currentNodes = array();

	/**
	 * @var array
	 */
	private $nodeTypesConfiguration = array();

	/**
	 * @return mixed
	 */
	abstract protected function getObjectManager();

	/**
	 * @return \TYPO3\TYPO3CR\Service\PublishingService $publishingService
	 */
	private function getPublishingService() {
		/** @var \TYPO3\TYPO3CR\Service\PublishingService $publishingService */
		return $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingService');
	}

	/**
	 * @BeforeScenario @fixtures
	 * @return void
	 */
	public function beforeScenarioDispatcher() {
		$this->resetNodeInstances();
		$this->resetContentDimensions();
	}

	/**
	 * @Given /^I have the following nodes:$/
	 * @When /^I create the following nodes:$/
	 */
	public function iHaveTheFollowingNodes(TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))), TRUE);
		} else {
			/** @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager $nodeTypeManager */
			$nodeTypeManager = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');

			$rows = $table->getHash();
			foreach ($rows as $row) {
				$path = $row['Path'];
				$name = implode('', array_slice(explode('/', $path), -1, 1));
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
				// If Language is set we pass them as explicit dimensions
				if (isset($row['Language'])) {
					$dimensions['language'] = explode(',', $row['Language']);
				} elseif (isset($row['Language'])) {
					$dimensions['language'] = array($row['Language']);
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
	}

	/**
	 * @Given /^I have the following content dimensions:$/
	 */
	public function iHaveTheFollowingContentDimensions(TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$contentDimensionRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
			$dimensions = array();
			foreach ($table->getHash() as $row) {
				$dimensions[$row['Identifier']] = array(
						'default' => $row['Default']
				);
			}
			$contentDimensionRepository->setDimensionsConfiguration($dimensions);
		}
	}

	/**
	 * @When /^I copy the node (into|after|before) path "([^"]*)" with the following context:$/
	 */
	public function iCopyANodeToPath($position, $path, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($position), 'string', escapeshellarg($path), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$node = $this->iShouldHaveOneNode();
			$referenceNode = $context->getNode($path);
			if ($position === 'into') {
				$node->copyInto($referenceNode, $node->getName() . '-1');
			} elseif ($position === 'after') {
				$node->copyAfter($referenceNode, $node->getName() . '-1');
			} else {
				$node->copyBefore($referenceNode, $node->getName() . '-1');
			}
			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @When /^I move the node (into|after|before) path "([^"]*)" with the following context:$/
	 */
	public function iMoveANodeToPath($position, $path, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($position), 'string', escapeshellarg($path), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$node = $this->iShouldHaveOneNode();
			$referenceNode = $context->getNode($path);
			if ($position === 'into') {
				$node->moveInto($referenceNode);
			} elseif ($position === 'after') {
				$node->moveAfter($referenceNode);
			} else {
				$node->moveBefore($referenceNode);
			}
		}
	}

	/**
	 * @When /^I get a node by path "([^"]*)" with the following context:$/
	 */
	public function iGetANodeByPathWithTheFollowingContext($path, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($path), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			if ($context->getWorkspace(FALSE) === NULL) {
				$context->getWorkspace(TRUE);

				$this->getSubcontext('flow')->persistAll();
				$this->resetNodeInstances();

				$context = $this->getContextForProperties($rows[0]);
			}

			$node = $context->getNode($path);
			if ($node !== NULL) {
				$this->currentNodes = array($node);
			} else {
				$this->currentNodes = array();
			}
		}
	}

	/**
	 * @When /^I get a node by identifier "([^"]*)" with the following context:$/
	 */
	public function iGetANodeByIdentifierWithTheFollowingContext($identifier, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($identifier), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$node = $context->getNodeByIdentifier($identifier);
			if ($node !== NULL) {
				$this->currentNodes = array($node);
			} else {
				$this->currentNodes = array();
			}
		}
	}

	/**
	 * @When /^I get the child nodes of "([^"]*)" with the following context:$/
	 */
	public function iGetTheChildNodesOfWithTheFollowingContext($path, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($path), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$node = $context->getNode($path);

			$this->currentNodes = $node->getChildNodes();
		}
	}

	/**
	 * @When /^I get the child nodes of "([^"]*)" with filter "([^"]*)" and the following context:$/
	 */
	public function iGetTheChildNodesOfWithFilterAndTheFollowingContext($path, $filter, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($path), 'string', escapeshellarg($filter), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$node = $context->getNode($path);

			$this->currentNodes = $node->getChildNodes($filter);
		}
	}

	/**
	 * @When /^I get the nodes on path "([^"]*)" to "([^"]*)" with the following context:$/
	 */
	public function iGetTheNodesOnPathToWithTheFollowingContext($startingPoint, $endPoint, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($startingPoint), 'string', escapeshellarg($endPoint), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$this->currentNodes = $context->getNodesOnPath($startingPoint, $endPoint);
		}
	}

	/**
	 * @When /^I publish the node$/
	 */
	public function iPublishNodeToWorkspaceWithTheFollowingContext() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$node = $this->iShouldHaveOneNode();

			$publishingService = $this->getPublishingService();
			$publishingService->publishNode($node);

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @When /^I publish the workspace "([^"]*)"$/
	 */
	public function iPublishTheWorkspace($sourceWorkspaceName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($sourceWorkspaceName)));
		} else {
			$sourceContext = $this->getContextForProperties(array('Workspace' => $sourceWorkspaceName));
			$sourceWorkspace = $sourceContext->getWorkspace();

			$liveContext = $this->getContextForProperties(array('Workspace' => 'live'));
			$liveWorkspace = $liveContext->getWorkspace();

			$sourceWorkspace->publish($liveWorkspace);

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @When /^I discard all changes in the workspace "([^"]*)"$/
	 */
	public function iDiscardTheWorkspace($workspaceName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($workspaceName)));
		} else {
			$context = $this->getContextForProperties(array('Workspace' => $workspaceName));
			$workspace = $context->getWorkspace();

			/** @var PublishingServiceInterface $publishingService */
			$publishingService = $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingServiceInterface');
			$publishingService->discardNodes($publishingService->getUnpublishedNodes($workspace));

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @When /^I use the publishing service to publish nodes in the workspace "([^"]*)" with the following context:$/
	 */
	public function iUseThePublishingServiceToPublishNodesInTheWorkspace($sourceWorkspaceName, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($sourceWorkspaceName), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			/** @var PublishingServiceInterface $publishingService */
			$publishingService = $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingServiceInterface');

			$rows = $table->getHash();
			$rows[0]['Workspace'] = $sourceWorkspaceName;

			$sourceContext = $this->getContextForProperties($rows[0]);
			$sourceWorkspace = $sourceContext->getWorkspace();

			$publishingService->publishNodes($publishingService->getUnpublishedNodes($sourceWorkspace));

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @Then /^I should (not |)be able to rename the node to "([^"]*)"$/
	 */
	public function iShouldBeAbleToRenameTheNodeTo($not, $newName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($not), 'string', escapeshellarg($newName)));
		} else {
			try {
				$this->iRenameTheNodeTo($newName);
			} catch (\Exception $exception) {
			}

			if (!empty($not) && !isset($exception)) {
				Assert::fail('Expected an exception while renaming the node');
			} elseif (empty($not) && isset($exception)) {
				throw $exception;
			}
		}
	}

	/**
	 * @Given /^I remove the node$/
	 */
	public function iRemoveTheNode() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$node = $this->iShouldHaveOneNode();
			$node->remove();

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @Given /^I set some property and rename the node to "([^"]*)"$/
	 */
	public function iSetSomePropertyAndRenameTheNodeTo($newName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($newName)));
		} else {
			$node = $this->iShouldHaveOneNode();
			$node->setHidden(NULL);
			$node->setName($newName);
		}
	}

	/**
	 * @Given /^I rename the node to "([^"]*)"$/
	 */
	public function iRenameTheNodeTo($newName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($newName)));
		} else {
			$node = $this->iShouldHaveOneNode();
			$node->setName($newName);
		}
	}

	/**
	 * @Given /^I move the node (before|after|into) the node with path "([^"]*)"$/
	 */
	public function iMoveTheNodeIntoTheNodeWithPath($action, $referenceNodePath) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($action), 'string', escapeshellarg($referenceNodePath)));
		} else {
			$node = $this->iShouldHaveOneNode();
			$referenceNode = $node->getContext()->getNode($referenceNodePath);
			switch ($action) {
				case 'before':
					$node->moveBefore($referenceNode);
					break;
				case 'after':
					$node->moveAfter($referenceNode);
					break;
				case 'into':
					$node->moveInto($referenceNode);
					break;
				default:
					throw new \InvalidArgumentException('Unknown move action "' . $action . '"');
			}

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @Then /^I should have one node$/
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function iShouldHaveOneNode() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			Assert::assertCount(1, $this->currentNodes);
			return $this->currentNodes[0];
		}
	}

	/**
	 * @Then /^I should have (\d+) nodes$/
	 */
	public function iShouldHaveNodes($count) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'integer', escapeshellarg($count)));
		} else {
			Assert::assertCount((integer)$count, $this->currentNodes);
		}
	}

	/**
	 * @Then /^the node property "([^"]*)" should be "([^"]*)"$/
	 */
	public function theNodePropertyShouldBe($propertyName, $propertyValue) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			Assert::assertEquals($propertyValue, $currentNode->getProperty($propertyName));
		}
	}

	/**
	 * @Then /^the node should be hidden in index$/
	 */
	public function theNodeShouldBeHiddenInIndex() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			Assert::assertTrue($currentNode->isHiddenInIndex(), 'The current node should be hidden in index, but it is not.');
		}
	}

	/**
	 * @When /^I set the node property "([^"]*)" to "([^"]*)"$/
	 */
	public function iSetTheNodePropertyTo($propertyName, $propertyValue) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			$currentNode->setProperty($propertyName, $propertyValue);

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @Given /^I set the node name to "([^"]*)"$/
	 */
	public function iSetTheNodeNameTo($name) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($name)));
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			$currentNode->setName($name);

			$this->getSubcontext('flow')->persistAll();
			$this->resetNodeInstances();
		}
	}

	/**
	 * @Then /^The node language dimension should be "([^"]*)"$/
	 */
	public function theNodeLanguagehouldBe($language) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($language)));
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			$dimensions = $currentNode->getDimensions();
			Assert::assertEquals($language, implode(',', $dimensions['language']), 'Language should match');
		}
	}

	/**
	 * @Then /^I should have a node with path "([^"]*)" and value "([^"]*)" for property "([^"]*)" for the following context:$/
	 */
	public function iShouldHaveANodeWithPathAndValueForPropertyForTheFollowingContext($path, $propertyValue, $propertyName, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg($path), 'string', escapeshellarg($propertyValue), 'string', escapeshellarg($propertyName), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$this->iGetANodeByPathWithTheFollowingContext($path, $table);
			$this->theNodePropertyShouldBe($propertyName, $propertyValue);
		}
	}

	/**
	 * @When /^I adopt the node (recursively |)to the following context:$/
	 */
	public function iAdoptTheNodeToTheFollowingContext($recursive, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($recursive), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$currentNode = $this->iShouldHaveOneNode();
			$this->currentNodes = array($context->adoptNode($currentNode, $recursive !== ''));
		}
	}

	/**
	 * @Then /^I should have the following nodes(| in any order):$/
	 */
	public function iShouldHaveTheFollowingNodes($orderIndependent, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($orderIndependent), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();

			Assert::assertCount(count($rows), $this->currentNodes, 'Current nodes should match count of examples');

			if ($orderIndependent === '') {
				foreach ($rows as $index => $row) {
					if (isset($row['Path'])) {
						Assert::assertEquals($row['Path'], $this->currentNodes[$index]->getPath(), 'Path should match on element ' . $index);
					}
					if (isset($row['Properties'])) {
						$nodeProperties = $this->currentNodes[$index]->getProperties();
						$testProperties = json_decode($row['Properties'], TRUE);
						foreach ($testProperties as $property => $value) {
							Assert::assertArrayHasKey($property, $nodeProperties, 'Expected property should exist');
							Assert::assertEquals($value, $nodeProperties[$property], 'The value for property "' . $property . '" should match the expected value');
						}
					}
					if (isset($row['Language'])) {
						$dimensions = $this->currentNodes[$index]->getDimensions();
						Assert::assertEquals($row['Language'], implode(',', $dimensions['language']), 'Language should match');
					}
				}
			} else {
				$currentNodes = $this->currentNodes;
				foreach ($currentNodes as $nodeIndex => $node) {
					foreach ($rows as $rowIndex => $row) {
						if (isset($row['Path']) && $row['Path'] !== $node->getPath()) {
							continue;
						}
						if (isset($row['Properties'])) {
							$nodeProperties = $node->getProperties();
							$testProperties = json_decode($row['Properties'], TRUE);
							foreach ($testProperties as $property => $value) {
								if (!isset($nodeProperties[$property]) || $nodeProperties[$property] !== $value) {
									continue 2;
								}
							}
						}
						if (isset($row['Language'])) {
							$dimensions = $node->getDimensions();
							if ($row['Language'] !== implode(',', $dimensions['language'])) {
								continue;
							}
						}
						unset($currentNodes[$nodeIndex]);
						unset($rows[$rowIndex]);
					}
				}
				Assert::assertEquals(array(), $rows, 'All examples should have matched');
				Assert::assertCount(0, $currentNodes, 'All nodes should be matched');
			}
		}
	}

	/**
	 * @Then /^the unpublished node count in workspace "([^"]*)" should be (\d+)$/
	 */
	public function theUnpublishedNodeCountInWorkspaceShouldBe($workspaceName, $count) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($workspaceName), 'integer', escapeshellarg($count)));
		} else {
			$workspaceRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
			$workspace = $workspaceRepository->findOneByName($workspaceName);
			$publishingService = $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingServiceInterface');
			$unpublishedNodesCount = $publishingService->getUnpublishedNodesCount($workspace);
			Assert::assertEquals($count, $unpublishedNodesCount);
		}
	}

	/**
	 * @Then /^print the nodes$/
	 */
	public function printTheNodes() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			foreach ($this->currentNodes as $node) {
				$this->printDebug($node->getPath());
			}
		}
	}

	/**
	 * @AfterScenario @fixtures
	 */
	public function resetCustomNodeTypes() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->overrideNodeTypes(array());
		}
	}

	/**
	 * @Given /^I have the following (additional |)NodeTypes configuration:$/
	 */
	public function iHaveTheFollowingNodetypesConfiguration($additional, PyStringNode $nodeTypesConfiguration) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($additional), 'integer', escapeshellarg($nodeTypesConfiguration)));
		} else {
			if (strlen($additional) > 0) {
				$configuration = Arrays::arrayMergeRecursiveOverrule($this->nodeTypesConfiguration, Yaml::parse($nodeTypesConfiguration->getRaw()));
			} else {
				$this->nodeTypesConfiguration = Yaml::parse($nodeTypesConfiguration->getRaw());
				$configuration = $this->nodeTypesConfiguration;
			}
			$this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->overrideNodeTypes($configuration);
		}
	}

	/**
	 * @Then /^I should (not |)be able to create a child node of type "([^"]*)"$/
	 */
	public function iShouldBeAbleToCreateAChildNodeOfType($not, $nodeTypeName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($not)), 'integer', escapeshellarg($nodeTypeName)));
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			$nodeType = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->getNodeType($nodeTypeName);
			if (empty($not)) {
				// ALLOWED to create node
				Assert::assertTrue($currentNode->isNodeTypeAllowedAsChildNode($nodeType), 'isNodeTypeAllowed returned the wrong value');

				// thus, the following line should not throw an exception
				$currentNode->createNode(uniqid('custom-node'), $nodeType);
			} else {
				// FORBIDDEN to create node
				Assert::assertFalse($currentNode->isNodeTypeAllowedAsChildNode($nodeType), 'isNodeTypeAllowed returned the wrong value');

				// thus, the following line should throw an exception
				try {
					$currentNode->createNode(uniqid('custom-node'), $nodeType);
					Assert::fail('It was possible to create a custom node, although it should have been prevented');
				} catch (\TYPO3\TYPO3CR\Exception\NodeConstraintException $nodeConstraintExceptio) {
					// Expected exception
				}
			}
		}
	}

	/**
	 * @When /^I get other node variants of the node$/
	 */
	public function iGetOtherNodeVariantsOfTheNode() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$currentNode = $this->iShouldHaveOneNode();
			$this->currentNodes = $currentNode->getOtherNodeVariants();
		}
	}

	/**
	 * @When /^I get node variants of "([^"]*)" with the following context:$/
	 */
	public function iGetNodeVariantsOfWithTheFollowingContext($identifier, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($identifier), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$this->currentNodes = $context->getNodeVariantsByIdentifier($identifier);
		}
	}

	/**
	 * Makes sure to reset all node instances which might still be stored in the NodeDataRepository, ContextFactory or
	 * NodeFactory.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function resetNodeInstances() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->reset();
			$this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->reset();
			$this->objectManager->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->reset();
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function resetContentDimensions() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$contentDimensionRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
			/** @var \TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository $contentDimensionRepository */

			// Set the content dimensions to a fixed value for Behat scenarios
			$contentDimensionRepository->setDimensionsConfiguration(array('language' => array('default' => 'mul_ZZ')));
		}
	}

	/**
	 *
	 *
	 * @param array $humanReadableContextProperties
	 * @param boolean $addDimensionDefaults
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 * @throws Exception
	 */
	protected function getContextForProperties(array $humanReadableContextProperties, $addDimensionDefaults = FALSE) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($humanReadableContextProperties), 'integer', escapeshellarg($addDimensionDefaults)));
		} else {
			/** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
			$contextFactory = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
			$contextProperties = array();
			if (isset($humanReadableContextProperties['Language'])) {
				$contextProperties['dimensions']['language'] = array($humanReadableContextProperties['Language'], 'mul_ZZ');
			}
			if (isset($humanReadableContextProperties['Language'])) {
				$contextProperties['dimensions']['language'] = Arrays::trimExplode(',', $humanReadableContextProperties['Language']);
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
				$contentDimensionRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
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

	/**
	 * @Then /^I expect to have (\d+) unpublished node[s]? for the following context:$/
	 */
	public function iExpectToHaveUnpublishedNodesForTheFollowingContext($nodeCount, TableNode $table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'integer', escapeshellarg($nodeCount), escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$context = $this->getContextForProperties($rows[0]);

			$publishingService = $this->getPublishingService();
			Assert::assertEquals((int)$nodeCount, count($publishingService->getUnpublishedNodes($context->getWorkspace())));
		}
	}

	/**
	 * @Then /^I should (not )?be able to set the "([^"]*)" property to "([^"]*)"$/
	 */
	public function iShouldNotBeAbleToSetThePropertyTo($not, $propertyName, $propertyValue) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
		} else {

			try {
				$this->currentNodes[0]->setProperty($propertyName, $propertyValue);
				if ($not === 'not') {
					Assert::fail('Property should not be settable on the current node!');
				}
			} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}
}
