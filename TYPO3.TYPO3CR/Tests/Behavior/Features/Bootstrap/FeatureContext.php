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

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\Yaml\Yaml;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\TYPO3CR\Service\PublishingServiceInterface;

/**
 * Features context
 */
class FeatureContext extends Behat\Behat\Context\BehatContext
{

    /**
     * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    private $currentNodes = array();

    /**
     * @var array
     */
    private $nodeTypesConfiguration = array();

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
    }

    /**
     * @Given /^I have the following nodes:$/
     * @When /^I create the following nodes:$/
     */
    public function iHaveTheFollowingNodes(TableNode $table)
    {
        /** @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager $nodeTypeManager */
        $nodeTypeManager = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');

        $rows = $table->getHash();
        foreach ($rows as $row) {
            $path = $row['Path'];
            $name = implode('', array_slice(explode('/', $path), -1, 1));
            $parentPath = implode('/', array_slice(explode('/', $path), 0, -1)) ?: '/';

            $context = $this->getContextForProperties($row, true);

            if (isset($row['Node Type']) && $row['Node Type'] !== '') {
                $nodeType = $nodeTypeManager->getNodeType($row['Node Type']);
            } else {
                $nodeType = null;
            }

            if (isset($row['Identifier'])) {
                $identifier = $row['Identifier'];
            } else {
                $identifier = null;
            }

            $parentNode = $context->getNode($parentPath);
            if ($parentNode === null) {
                throw new Exception(sprintf('Could not get parent node with path %s to create node %s', $parentPath, $path));
            }

            $node = $parentNode->createNode($name, $nodeType, $identifier);

            if (isset($row['Properties']) && $row['Properties'] !== '') {
                $properties = json_decode($row['Properties'], true);
                if ($properties === null) {
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
     * @return mixed
     */
    private function getObjectManager()
    {
        return $this->getSubcontext('flow')->getObjectManager();
    }

    /**
     *
     *
     * @param array $humanReadableContextProperties
     * @param boolean $addDimensionDefaults
     * @return \TYPO3\TYPO3CR\Domain\Service\Context
     * @throws Exception
     */
    protected function getContextForProperties(array $humanReadableContextProperties, $addDimensionDefaults = false)
    {
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

    /**
     * Makes sure to reset all node instances which might still be stored in the NodeDataRepository, ContextFactory or
     * NodeFactory.
     *
     * @return void
     * @throws Exception
     */
    public function resetNodeInstances()
    {
        $this->getSubcontext('flow')->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->reset();
        $this->getSubcontext('flow')->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->reset();
        $this->getSubcontext('flow')->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->reset();
    }

    /**
     * @Given /^I have the following content dimensions:$/
     */
    public function iHaveTheFollowingContentDimensions(TableNode $table)
    {
        $contentDimensionRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
        $dimensions = array();
        foreach ($table->getHash() as $row) {
            $dimensions[$row['Identifier']] = array(
                'default' => $row['Default']
            );
        }
        $contentDimensionRepository->setDimensionsConfiguration($dimensions);
    }

    /**
     * @When /^I copy the node (into|after|before) path "([^"]*)" with the following context:$/
     */
    public function iCopyANodeToPath($position, $path, TableNode $table)
    {
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
    }

    /**
     * @Then /^I should have one node$/
     *
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     */
    public function iShouldHaveOneNode()
    {
        Assert::assertCount(1, $this->currentNodes);

        return $this->currentNodes[0];
    }

    /**
     * @Given /^I set some property and rename the node to "([^"]*)"$/
     */
    public function iSetSomePropertyAndRenameTheNodeTo($newName)
    {
        $node = $this->iShouldHaveOneNode();
        $node->setHidden(null);
        $node->setName($newName);
    }

    /**
     * @Then /^I should (not |)be able to rename the node to "([^"]*)"$/
     */
    public function iShouldBeAbleToRenameTheNodeTo($not, $newName)
    {
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

    /**
     * @Given /^I rename the node to "([^"]*)"$/
     *
     */
    public function iRenameTheNodeTo($newName)
    {
        $node = $this->iShouldHaveOneNode();
        $node->setName($newName);
    }

    /**
     * @When /^I move the node (into|after|before) path "([^"]*)" with the following context:$/
     */
    public function iMoveANodeToPath($position, $path, TableNode $table)
    {
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

    /**
     * @When /^I get a node by identifier "([^"]*)" with the following context:$/
     */
    public function iGetANodeByIdentifierWithTheFollowingContext($identifier, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $node = $context->getNodeByIdentifier($identifier);
        if ($node !== null) {
            $this->currentNodes = array($node);
        } else {
            $this->currentNodes = array();
        }
    }

    /**
     * @When /^I get the child nodes of "([^"]*)" with the following context:$/
     */
    public function iGetTheChildNodesOfWithTheFollowingContext($path, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $node = $context->getNode($path);

        $this->currentNodes = $node->getChildNodes();
    }

    /**
     * @When /^I get the child nodes of "([^"]*)" with filter "([^"]*)" and the following context:$/
     */
    public function iGetTheChildNodesOfWithFilterAndTheFollowingContext($path, $filter, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $node = $context->getNode($path);

        $this->currentNodes = $node->getChildNodes($filter);
    }

    /**
     * @When /^I get the nodes on path "([^"]*)" to "([^"]*)" with the following context:$/
     */
    public function iGetTheNodesOnPathToWithTheFollowingContext($startingPoint, $endPoint, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $this->currentNodes = $context->getNodesOnPath($startingPoint, $endPoint);
    }

    /**
     * @When /^I publish the node$/
     */
    public function iPublishNodeToWorkspaceWithTheFollowingContext()
    {
        $node = $this->iShouldHaveOneNode();

        $publishingService = $this->getPublishingService();
        $publishingService->publishNode($node);

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @return \TYPO3\TYPO3CR\Service\PublishingService $publishingService
     */
    private function getPublishingService()
    {
        /** @var \TYPO3\TYPO3CR\Service\PublishingService $publishingService */
        return $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingService');
    }

    /**
     * @When /^I publish the workspace "([^"]*)"$/
     */
    public function iPublishTheWorkspace($sourceWorkspaceName)
    {
        $sourceContext = $this->getContextForProperties(array('Workspace' => $sourceWorkspaceName));
        $sourceWorkspace = $sourceContext->getWorkspace();

        $liveContext = $this->getContextForProperties(array('Workspace' => 'live'));
        $liveWorkspace = $liveContext->getWorkspace();

        $sourceWorkspace->publish($liveWorkspace);

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @When /^I discard all changes in the workspace "([^"]*)"$/
     */
    public function iDiscardTheWorkspace($workspaceName)
    {
        $context = $this->getContextForProperties(array('Workspace' => $workspaceName));
        $workspace = $context->getWorkspace();

        /** @var PublishingServiceInterface $publishingService */
        $publishingService = $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingServiceInterface');
        $publishingService->discardNodes($publishingService->getUnpublishedNodes($workspace));

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @When /^I use the publishing service to publish nodes in the workspace "([^"]*)" with the following context:$/
     */
    public function iUseThePublishingServiceToPublishNodesInTheWorkspace($sourceWorkspaceName, TableNode $table)
    {
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

    /**
     * @Given /^I remove the node$/
     */
    public function iRemoveTheNode()
    {
        $node = $this->iShouldHaveOneNode();
        $node->remove();

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @Given /^I move the node (before|after|into) the node with path "([^"]*)"$/
     */
    public function iMoveTheNodeIntoTheNodeWithPath($action, $referenceNodePath)
    {
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

    /**
     * @Then /^I should have (\d+) nodes$/
     */
    public function iShouldHaveNodes($count)
    {
        Assert::assertCount((integer)$count, $this->currentNodes);
    }

    /**
     * @Then /^the node should be hidden in index$/
     */
    public function theNodeShouldBeHiddenInIndex()
    {
        $currentNode = $this->iShouldHaveOneNode();
        Assert::assertTrue($currentNode->isHiddenInIndex(), 'The current node should be hidden in index, but it is not.');
    }

    /**
     * @When /^I set the node property "([^"]*)" to "([^"]*)"$/
     */
    public function iSetTheNodePropertyTo($propertyName, $propertyValue)
    {
        $currentNode = $this->iShouldHaveOneNode();
        $currentNode->setProperty($propertyName, $propertyValue);

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @Given /^I set the node name to "([^"]*)"$/
     */
    public function iSetTheNodeNameTo($name)
    {
        $currentNode = $this->iShouldHaveOneNode();
        $currentNode->setName($name);

        $this->getSubcontext('flow')->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @Then /^The node language dimension should be "([^"]*)"$/
     */
    public function theNodeLanguagehouldBe($language)
    {
        $currentNode = $this->iShouldHaveOneNode();
        $dimensions = $currentNode->getDimensions();
        Assert::assertEquals($language, implode(',', $dimensions['language']), 'Language should match');
    }

    /**
     * @Then /^I should have a node with path "([^"]*)" and value "([^"]*)" for property "([^"]*)" for the following context:$/
     */
    public function iShouldHaveANodeWithPathAndValueForPropertyForTheFollowingContext($path, $propertyValue, $propertyName, TableNode $table)
    {
        $this->iGetANodeByPathWithTheFollowingContext($path, $table);
        $this->theNodePropertyShouldBe($propertyName, $propertyValue);
    }

    /**
     * @When /^I get a node by path "([^"]*)" with the following context:$/
     */
    public function iGetANodeByPathWithTheFollowingContext($path, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        if ($context->getWorkspace(false) === null) {
            $context->getWorkspace(true);

            $this->getSubcontext('flow')->persistAll();
            $this->resetNodeInstances();

            $context = $this->getContextForProperties($rows[0]);
        }

        $node = $context->getNode($path);
        if ($node !== null) {
            $this->currentNodes = array($node);
        } else {
            $this->currentNodes = array();
        }
    }

    /**
     * @Then /^the node property "([^"]*)" should be "([^"]*)"$/
     */
    public function theNodePropertyShouldBe($propertyName, $propertyValue)
    {
        $currentNode = $this->iShouldHaveOneNode();
        Assert::assertEquals($propertyValue, $currentNode->getProperty($propertyName));
    }

    /**
     * @When /^I adopt the node to the following context:$/
     */
    public function iAdoptTheNodeToTheFollowingContext(TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $currentNode = $this->iShouldHaveOneNode();
        $this->currentNodes = array($context->adoptNode($currentNode));
    }

    /**
     * @Then /^I should have the following nodes:$/
     */
    public function iShouldHaveTheFollowingNodes(TableNode $table)
    {
        $rows = $table->getHash();

        Assert::assertCount(count($rows), $this->currentNodes, 'Current nodes should match count of examples');

        foreach ($rows as $index => $row) {
            if (isset($row['Path'])) {
                Assert::assertEquals($row['Path'], $this->currentNodes[$index]->getPath(), 'Path should match on element ' . $index);
            }
            if (isset($row['Properties'])) {
                $nodeProperties = $this->currentNodes[$index]->getProperties();
                $testProperties = json_decode($row['Properties'], true);
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
    }

    /**
     * @Given /^the unpublished node count in workspace "([^"]*)" should be (\d+)$/
     */
    public function theUnpublishedNodeCountInWorkspaceShouldBe($workspaceName, $count)
    {
        $workspaceRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository');
        $workspace = $workspaceRepository->findOneByName($workspaceName);
        $publishingService = $this->getObjectManager()->get('TYPO3\TYPO3CR\Service\PublishingServiceInterface');
        $unpublishedNodesCount = $publishingService->getUnpublishedNodesCount($workspace);
        Assert::assertEquals($count, $unpublishedNodesCount);
    }

    /**
     * @Then /^print the nodes$/
     */
    public function printTheNodes()
    {
        foreach ($this->currentNodes as $node) {
            $this->printDebug($node->getPath());
        }
    }

    /**
     * @AfterScenario @fixtures
     */
    public function resetCustomNodeTypes()
    {
        $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->overrideNodeTypes(array());
    }

    /**
     * @Given /^I have the following (additional |)NodeTypes configuration:$/
     */
    public function iHaveTheFollowingNodetypesConfiguration($additional, PyStringNode $nodeTypesConfiguration)
    {
        if (strlen($additional) > 0) {
            $configuration = Arrays::arrayMergeRecursiveOverrule($this->nodeTypesConfiguration, Yaml::parse($nodeTypesConfiguration->getRaw()));
        } else {
            $this->nodeTypesConfiguration = Yaml::parse($nodeTypesConfiguration->getRaw());
            $configuration = $this->nodeTypesConfiguration;
        }
        $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->overrideNodeTypes($configuration);
    }

    /**
     * @Then /^I should (not |)be able to create a child node of type "([^"]*)"$/
     */
    public function iShouldBeAbleToCreateAChildNodeOfType($not, $nodeTypeName)
    {
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

    /**
     * @Then /^I expect to have (\d+) unpublished node[s]? for the following context:$/
     */
    public function iExpectToHaveUnpublishedNodesForTheFollowingContext($nodeCount, TableNode $table)
    {
        $rows = $table->getHash();
        $context = $this->getContextForProperties($rows[0]);

        $publishingService = $this->getPublishingService();
        Assert::assertEquals((int)$nodeCount, count($publishingService->getUnpublishedNodes($context->getWorkspace())));
    }

    /**
     * @BeforeScenario @fixtures
     * @return void
     */
    public function beforeScenarioDispatcher()
    {
        $this->resetNodeInstances();
        $this->resetContentDimensions();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function resetContentDimensions()
    {
        $contentDimensionRepository = $this->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository');
        /** @var \TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository $contentDimensionRepository */

        // Set the content dimensions to a fixed value for Behat scenarios
        $contentDimensionRepository->setDimensionsConfiguration(array('language' => array('default' => 'mul_ZZ')));
    }
}
