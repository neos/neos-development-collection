<?php
namespace TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Behat\Gherkin\Node\TableNode;
use TYPO3\Flow\Annotations as Flow;
use PHPUnit_Framework_Assert as Assert;
use TYPO3\Flow\Security\Exception\AccessDeniedException;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires the following properties to be available:
 * * $this->nodeAuthorizationService
 * * $this->nodeTypeManager
 *
 * Note: This trait expects the IsolatedBehatStepsTrait and the NodeOperationsTrait to be available!
 */
trait NodeAuthorizationTrait {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Service\AuthorizationService
	 */
	protected $nodeAuthorizationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @param string $expectedResult
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if editing this node is granted$/
	 */
	public function iShouldGetTrueWhenAskingTheNodeAuthorizationServiceIfEditingThisNodeIsGranted($expectedResult) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg(trim($expectedResult))));
		} else {
			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToEditNode($this->currentNodes[0]) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToEditNode($this->currentNodes[0]) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}

	/**
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if editing the "([^"]*)" property is granted$/
	 */
	public function iShouldGetTrueWhenAskingTheNodeAuthorizationServiceIfEditingThePropertyIsGranted($expectedResult, $propertyName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($expectedResult)), 'string', escapeshellarg($propertyName)));
		} else {
			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToEditNode($this->currentNodes[0], $propertyName) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToEditNode($this->currentNodes[0], $propertyName) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}

	/**
	 * @param TableNode $table
	 * @Then /^I should get the following list of denied node properties from the node authorization service:$/
	 */
	public function iShouldGetTheFollowingListOfDeniedNodePropertiesFromTheNodeAuthorizationService($table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$deniedPropertyNames = $this->nodeAuthorizationService->getDeniedNodePropertiesForEditing($this->currentNodes[0]);

			if (count($rows) !== count($deniedPropertyNames)) {
				Assert::fail('The node authorization service did not return the expected amount of node property names! Got: ' . implode(', ', $deniedPropertyNames));
			}

			foreach ($rows as $row) {
				if (in_array($row['propertyName'], $deniedPropertyNames) === FALSE) {
					Assert::fail('The following property name has not been returned by the node authorization service: ' . $row['propertyName']);
				}
			}
		}
	}

	/**
	 * @param string $not
	 * @throws AccessDeniedException
	 * @Then /^I should (not )?be granted to set any of the node's attributes$/
	 */
	public function iShouldNotBeGrantedToSetAnyOfTheNodesAttributes($not = '') {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg(trim($not))));
		} else {

			try {
				$this->currentNodes[0]->setName('someNewName');
				if ($not === 'not') {
					Assert::fail('Name should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->removeProperty('title');
				if ($not === 'not') {
					Assert::fail('Title should not be removable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setContentObject($this->currentNodes[0]->getNodeData());
				if ($not === 'not') {
					Assert::fail('Content object should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->unsetContentObject();
				if ($not === 'not') {
					Assert::fail('Content object should not be unsettable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$nodeTypeManager = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
				$this->currentNodes[0]->setNodeType($nodeTypeManager->getNodeType('TYPO3.Neos:Node'));
				if ($not === 'not') {
					Assert::fail('NodeType should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setHidden(TRUE);
				if ($not === 'not') {
					Assert::fail('Hidden flag should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setHiddenBeforeDateTime(new \DateTime());
				if ($not === 'not') {
					Assert::fail('Hidden before should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setHiddenAfterDateTime(new \DateTime());
				if ($not === 'not') {
					Assert::fail('Hidden after should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setHiddenInIndex(TRUE);
				if ($not === 'not') {
					Assert::fail('Hidden in index should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}

			try {
				$this->currentNodes[0]->setAccessRoles(array());
				if ($not === 'not') {
					Assert::fail('Access roles in index should not be settable on the current node!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}

	/**
	 * @param string $not
	 * @param string $nodeName
	 * @param string $nodeType
	 * @throws \Exception
	 * @Then /^I should (not )?be granted to create a new "([^"]*)" child node of type "([^"]*)"$/
	 */
	public function iShouldNotBeGrantedToCreateANewChildNodeOfType($not, $nodeName, $nodeType) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($nodeName), 'string', escapeshellarg($nodeType)));
		} else {
			/** @var NodeTypeManager $nodeTypeManager */
			$nodeTypeManager = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');

			try {
				$this->currentNodes[0]->createNode($nodeName, $nodeTypeManager->getNodeType($nodeType));
				if ($not === 'not') {
					Assert::fail('Should not be able to create a child node of type "' . $nodeType . '"!');
				}
			} catch (AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}

	/**
	 * @param string $expectedResult
	 * @param string $nodeName
	 * @param string $nodeTypeName
	 * @throws NodeTypeNotFoundException
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if creating a new "([^"]*)" child node of type "([^"]*)" is granted$/
	 */
	public function iShouldGetFalseWhenAskingTheNodeAuthorizationServiceIfCreatingAChildNodeOfTypeIsGranted($expectedResult, $nodeName, $nodeTypeName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg(trim($expectedResult)), 'string', escapeshellarg($nodeName), 'string', escapeshellarg($nodeTypeName)));
		} else {
			/** @var NodeTypeManager $nodeTypeManager */
			$nodeTypeManager = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
			$nodeType = $nodeTypeManager->getNodeType($nodeTypeName);

			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToCreateNode($this->currentNodes[0], $nodeType) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToCreateNode($this->currentNodes[0], $nodeType) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}

	/**
	 * @Then /^I should get the following list of denied node types for this node from the node authorization service:$/
	 */
	public function iShouldGetTheFollowingListOfDeniedNodeTypesForThisNodeFromTheNodeAuthorizationService($table) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg('TYPO3\Flow\Tests\Functional\Command\TableNode'), escapeshellarg(json_encode($table->getHash()))));
		} else {
			$rows = $table->getHash();
			$deniedNodeTypeNames = $this->nodeAuthorizationService->getNodeTypeNamesDeniedForCreation($this->currentNodes[0]);

			if (count($rows) !== count($deniedNodeTypeNames)) {
				Assert::fail('The node authorization service did not return the expected amount of node type names! Got: ' . implode(', ', $deniedNodeTypeNames));
			}

			foreach ($rows as $row) {
				if (in_array($row['nodeTypeName'], $deniedNodeTypeNames) === FALSE) {
					Assert::fail('The following node type name has not been returned by the node authorization service: ' . $row['nodeTypeName']);
				}
			}
		}
	}

	/**
	 * @Then /^I should get the list of all available node types as denied node types for this node from the node authorization service$/
	 */
	public function iShouldGetTheListOfAllAvailableNodeTypesAsDeniedNodeTypesForThisNodeFromTheNodeAuthorizationService() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			$availableNodeTypes = $this->nodeTypeManager->getNodeTypes();
			$deniedNodeTypeNames = $this->nodeAuthorizationService->getNodeTypeNamesDeniedForCreation($this->currentNodes[0]);

			if (count($availableNodeTypes) !== count($deniedNodeTypeNames)) {
				Assert::fail('The node authorization service did not return the expected amount of node type names! Got: ' . implode(', ', $deniedNodeTypeNames));
			}

			foreach ($availableNodeTypes as $nodeType) {
				if (in_array($nodeType, $deniedNodeTypeNames) === FALSE) {
					Assert::fail('The following node type name has not been returned by the node authorization service: ' . $nodeType);
				}
			}
		}
	}


	/**
	 * @param string $not
	 * @Then /^I should (not )?be granted to remove the node$/
	 */
	public function iShouldNotBeGrantedToRemoveTheNode($not = '') {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg(trim($not))));
		} else {

			try {
				$this->currentNodes[0]->remove();
				if ($not === 'not') {
					Assert::fail('Name should not be settable on the current node!');
				}
			} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}

	/**
	 * @param string $expectedResult
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if removal of the node is granted$/
	 */
	public function iShouldGetFalseWhenAskingTheNodeAuthorizationServiceIfRemovalOfTheNodeIsGranted($expectedResult) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg(trim($expectedResult))));
		} else {

			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToRemoveNode($this->currentNodes[0]) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToRemoveNode($this->currentNodes[0]) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}

	/**
	 * @Then /^I should (not )?be granted to get the "([^"]*)" property$/
	 */
	public function iShouldNotBeGrantedToGetTheProperty($not, $propertyName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($propertyName)));
		} else {
			/** @var NodeInterface $currentNode */
			$currentNode = $this->currentNodes[0];
			try {
				switch ($propertyName) {
					case 'name':
						$propertyValue = $currentNode->getName();
						break;
					case 'hidden':
						$propertyValue = $currentNode->isHidden();
						break;
					case 'hiddenBeforeDateTime':
						$propertyValue = $currentNode->getHiddenBeforeDateTime();
						break;
					case 'hiddenAfterDateTime':
						$propertyValue = $currentNode->getHiddenAfterDateTime();
						break;
					case 'hiddenInIndex':
						$propertyValue = $currentNode->isHiddenInIndex();
						break;
					case 'accessRoles':
						$propertyValue = $currentNode->getAccessRoles();
						break;
					default:
						$propertyValue = $currentNode->getProperty($propertyName);
						break;
				}
				if ($not === 'not') {
					Assert::fail('Property should not be gettable on the current node! But we could read the value: "' . $propertyValue . '"');
				}
			} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}

	/**
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if getting the "([^"]*)" property is granted$/
	 */
	public function iShouldGetFalseWhenAskingTheNodeAuthorizationServiceIfGettingThePropertyIsGranted($expectedResult, $propertyName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($expectedResult)), 'string', escapeshellarg($propertyName)));
		} else {
			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToReadNodeProperty($this->currentNodes[0], $propertyName) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToReadNodeProperty($this->currentNodes[0], $propertyName) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}

	/**
	 * @Then /^I should (not )?be granted to set the "([^"]*)" property to "([^"]*)"$/
	 */
	public function iShouldNotBeGrantedToSetThePropertyTo($not, $propertyName, $value) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($propertyName), 'string', escapeshellarg($value)));
		} else {
			/** @var NodeInterface $currentNode */
			$currentNode = $this->currentNodes[0];
			try {
				switch ($propertyName) {
					case 'name':
						$currentNode->setName($value);
						break;
					case 'hidden':
						$currentNode->setHidden($value);
						break;
					case 'hiddenBeforeDateTime':
						$currentNode->setHiddenBeforeDateTime(new \DateTime($value));
						break;
					case 'hiddenAfterDateTime':
						$currentNode->setHiddenAfterDateTime(new \DateTime($value));
						break;
					case 'hiddenInIndex':
						$currentNode->setHiddenInIndex($value);
						break;
					case 'accessRoles':
						$currentNode->setAccessRoles(array($value));
						break;
					default:
						$currentNode->setProperty($propertyName, $value);
						break;
				}
				if ($not === 'not') {
					Assert::fail('Property should not be settable on the current node! But we could set the value of "' . $propertyName . '" to "' . $value . '"');
				}
			} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
				if ($not !== 'not') {
					throw $exception;
				}
			}
		}
	}

	/**
	 * @Given /^I should get (TRUE|FALSE) when asking the node authorization service if setting the "([^"]*)" property is granted$/
	 */
	public function iShouldGetFalseWhenAskingTheNodeAuthorizationServiceIfSettingThePropertyIsGranted($expectedResult, $propertyName) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($expectedResult)), 'string', escapeshellarg($propertyName)));
		} else {
			if ($expectedResult === 'TRUE') {
				if ($this->nodeAuthorizationService->isGrantedToEditNodeProperty($this->currentNodes[0], $propertyName) !== TRUE) {
					Assert::fail('The node authorization service did not return TRUE!');
				}
			} else {
				if ($this->nodeAuthorizationService->isGrantedToEditNodeProperty($this->currentNodes[0], $propertyName) !== FALSE) {
					Assert::fail('The node authorization service did not return FALSE!');
				}
			}
		}
	}
}
