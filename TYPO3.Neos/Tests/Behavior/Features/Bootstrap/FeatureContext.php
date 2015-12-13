<?php

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use PHPUnit_Framework_Assert as Assert;
use TYPO3\Flow\Utility\Arrays;

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext
{
    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Behat\Mink\Element\ElementInterface
     */
    protected $selectedContentElement;

    /**
     * @var string
     */
    protected $lastExportedSiteXmlPathAndFilename = '';

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
        $this->objectManager = $this->getSubcontext('flow')->getObjectManager();
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
     * @return \TYPO3\Neos\Service\PublishingService $publishingService
     */
    private function getPublishingService()
    {
        /** @var \TYPO3\TYPO3CR\Service\PublishingService $publishingService */
        return $this->getObjectManager()->get('TYPO3\Neos\Service\PublishingService');
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
     * @Given /^I am not authenticated$/
     */
    public function iAmNotAuthenticated()
    {
        // Do nothing, every scenario has a new session
    }

    /**
     * @Then /^I should see a login form$/
     */
    public function iShouldSeeALoginForm()
    {
        $this->assertSession()->fieldExists('Username');
        $this->assertSession()->fieldExists('Password');
    }

    /**
     * @Given /^the following users exist:$/
     */
    public function theFollowingUsersExist(TableNode $table)
    {
        $rows = $table->getHash();
        /** @var \TYPO3\Neos\Domain\Factory\UserFactory $userFactory */
        $userFactory = $this->objectManager->get('TYPO3\Neos\Domain\Factory\UserFactory');
        /** @var \TYPO3\Party\Domain\Repository\PartyRepository $partyRepository */
        $partyRepository = $this->objectManager->get('TYPO3\Party\Domain\Repository\PartyRepository');
        /** @var \TYPO3\Flow\Security\AccountRepository $accountRepository */
        $accountRepository = $this->objectManager->get('TYPO3\Flow\Security\AccountRepository');
        foreach ($rows as $row) {
            $roleIdentifiers = array_map(function ($role) {
                return 'TYPO3.Neos:' . $role;
            }, Arrays::trimExplode(',', $row['roles']));
            $user = $userFactory->create($row['username'], $row['password'], $row['firstname'], $row['lastname'], $roleIdentifiers);

            $partyRepository->add($user);
            $accounts = $user->getAccounts();
            foreach ($accounts as $account) {
                $accountRepository->add($account);
            }
        }
        $this->getSubcontext('flow')->persistAll();
    }

    /**
     * @Given /^I am authenticated with "([^"]*)" and "([^"]*)" for the backend$/
     */
    public function iAmAuthenticatedWithAndForTheBackend($username, $password)
    {
        $this->visit('/neos/login');
        $this->fillField('Username', $username);
        $this->fillField('Password', $password);
        $this->pressButton('Login');
    }

    /**
     * @Then /^I should be on the "([^"]*)" page$/
     */
    public function iShouldBeOnThePage($page)
    {
        switch ($page) {
            case 'Login':
                $this->assertSession()->addressEquals('/neos/login');
            break;
            default:
                throw new PendingException();
        }
    }

    /**
     * @Then /^I should be in the "([^"]*)" module$/
     */
    public function iShouldBeInTheModule($moduleName)
    {
        switch ($moduleName) {
            case 'Content':
                $this->assertSession()->addressMatches('/^\/(?!neos).*@.+$/');
            break;
            default:
                throw new PendingException();
        }
    }

    /**
     * @When /^I follow "([^"]*)" in the main menu$/
     */
    public function iFollowInTheMainMenu($link)
    {
        $this->assertElementOnPage('ul.nav');
        $this->getSession()->getPage()->find('css', 'ul.nav')->findLink($link)->click();
    }

    /**
     * @Given /^I should be logged in as "([^"]*)"$/
     */
    public function iShouldBeLoggedInAs($name)
    {
        $this->assertSession()->elementTextContains('css', '#neos-user-actions .neos-user-menu', $name);
    }

    /**
     * @Then /^I should not be logged in$/
     */
    public function iShouldNotBeLoggedIn()
    {
        if ($this->getSession()->getPage()->findButton('logout')) {
            Assert::fail('"Logout" Button not expected');
        }
    }

    /**
     * @Given /^I should see the page title "([^"]*)"$/
     */
    public function iShouldSeeThePageTitle($title)
    {
        $this->assertSession()->elementTextContains('css', 'title', $title);
    }

    /**
     * @Then /^I should not see the top bar$/
     */
    public function iShouldNotSeeTheTopBar()
    {
        return array(
            new \Behat\Behat\Context\Step\Then('I should not see "Navigate"'),
            new \Behat\Behat\Context\Step\Then('I should not see "Edit / Preview"'),
        );
        //c1$this->assertElementOnPage('.neos-previewmode #neos-top-bar');
    }

    /**
     * @Given /^the Previewbutton should be active$/
     */
    public function thePreviewButtonShouldBeActive()
    {
        $button = $this->getSession()->getPage()->find('css', '.neos-full-screen-close > .neos-pressed');
        if ($button === null) {
            throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), 'button', 'id|name|label|value');
        }

        Assert::assertTrue($button->hasClass('neos-pressed'), 'Button should be pressed');
    }

    /**
     * @Given /^I imported the site "([^"]*)"$/
     */
    public function iImportedTheSite($packageKey)
    {
        /** @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository $nodeDataRepository */
        $nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        /** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
        $contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $contentContext = $contextFactory->create(array('workspace' => 'live'));
        \TYPO3\Flow\Reflection\ObjectAccess::setProperty($nodeDataRepository, 'context', $contentContext, true);

        /** @var \TYPO3\Neos\Domain\Service\SiteImportService $siteImportService */
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
        $siteImportService->importFromPackage($packageKey, $contentContext);

        $this->getSubcontext('flow')->persistAll();
    }

    /**
     * @When /^I go to the "([^"]*)" module$/
     */
    public function iGoToTheModule($module)
    {
        switch ($module) {
            case 'Administration / Site Management':
                $this->visit('/neos/administration/sites');
            break;
            case 'Administration / User Management':
                $this->visit('/neos/administration/users');
            break;
            default:
                throw new PendingException();
        }
    }

    /**
     * Clear the content cache. Since this could be needed for multiple Flow contexts, we have to do it on the
     * filesystem for now. Using a different cache backend than the FileBackend will not be possible with this approach.
     *
     * @BeforeScenario @fixtures
     */
    public function clearContentCache()
    {
        $directories = array_merge(
            glob(FLOW_PATH_DATA . 'Temporary/*/Cache/Data/TYPO3_TypoScript_Content'),
            glob(FLOW_PATH_DATA . 'Temporary/*/*/Cache/Data/TYPO3_TypoScript_Content')
        );
        if (is_array($directories)) {
            foreach ($directories as $directory) {
                \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($directory);
            }
        }
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function removeTestSitePackages()
    {
        $directories = glob(FLOW_PATH_PACKAGES . 'Sites/Test.*');
        if (is_array($directories)) {
            foreach ($directories as $directory) {
                \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($directory);
            }
        }
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function resetContextFactory()
    {
        /** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
        $contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        \TYPO3\Flow\Reflection\ObjectAccess::setProperty($contextFactory, 'contextInstances', array(), true);
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function resetContentDimensionConfiguration()
    {
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

    /**
     * @Then /^I should see the following sites in a table:$/
     */
    public function iShouldSeeTheFollowingSitesInATable(TableNode $table)
    {
        $sites = $table->getHash();

        $tableLocator = '.neos-module-wrap table.neos-table';
        $sitesTable = $this->assertSession()->elementExists('css', $tableLocator);

        $siteRows = $sitesTable->findAll('css', 'tbody tr');
        $actualSites = array_map(function ($row) {
            $firstColumn = $row->find('css', 'td:nth-of-type(1)');
            if ($firstColumn !== null) {
                return array(
                    'name' => $firstColumn->getText()
                );
            }
        }, $siteRows);

        $sortByName = function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        };
        usort($sites, $sortByName);
        usort($actualSites, $sortByName);

        Assert::assertEquals($sites, $actualSites);
    }

    /**
     * @Given /^I follow "([^"]*)" for site "([^"]*)"$/
     */
    public function iFollowForSite($link, $siteName)
    {
        $rowLocator = sprintf("//table[@class='neos-table']//tr[td/text()='%s']", $siteName);
        $siteRow = $this->assertSession()->elementExists('xpath', $rowLocator);
        $siteRow->findLink($link)->click();
    }

    /**
     * @When /^I select the first content element$/
     */
    public function iSelectTheFirstContentElement()
    {
        $element = $this->assertSession()->elementExists('css', '.neos-contentelement');
        $element->click();

        $this->selectedContentElement = $element;
    }

    /**
     * @When /^I select the first headline content element$/
     */
    public function iSelectTheFirstHeadlineContentElement()
    {
        $element = $this->assertSession()->elementExists('css', '.typo3-neos-nodetypes-headline');
        $element->click();

        $this->selectedContentElement = $element;
    }

    /**
     * @Given /^I set the content to "([^"]*)"$/
     */
    public function iSetTheContentTo($content)
    {
        $editable = $this->assertSession()->elementExists('css', '.neos-inline-editable', $this->selectedContentElement);

        $this->spinWait(function () use ($editable) {
            return $editable->hasAttribute('contenteditable');
        }, 10000, 'editable has contenteditable attribute set');

        $editable->setValue($content);
    }

    /**
     * @param callable $callback
     * @param integer $timeout Timeout in milliseconds
     * @param string $message
     */
    public function spinWait($callback, $timeout, $message = '')
    {
        $waited = 0;
        while ($callback() !== true) {
            if ($waited > $timeout) {
                Assert::fail($message);

                return;
            }
            usleep(50000);
            $waited += 50;
        }
    }

    /**
     * @Given /^I wait for the changes to be saved$/
     */
    public function iWaitForTheChangesToBeSaved()
    {
        $this->getSession()->wait(30000, '$(".neos-indicator-saved").length > 0');
        $this->assertSession()->elementExists('css', '.neos-indicator-saved');
    }

    /**
     * @When /^I wait for the "([^"]*)"( button) to be visible$/
     */
    public function iWaitForElement($elementName)
    {
        $elementSelector = $this->getNamedElementSelector($elementName);

        $this->getSession()->wait(30000, '$("' . $elementSelector . '").length > 0');
        $this->assertSession()->elementExists('css', $elementSelector);
    }

    /**
     * @param string $elementName
     * @return string
     */
    protected function getNamedElementSelector($elementName)
    {
        switch ($elementName) {
            case 'Open full screen':
                return '.neos-full-screen-open';
            case 'Close full screen':
                return '.neos-full-screen-close';
            default:
                Assert::fail('No element definition found for named element "' . $elementName . '"');
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function locatePath($path)
    {
        return parent::locatePath($this->getSubcontext('flow')->resolvePath($path));
    }

    /**
     * @Given /^I have the site "([^"]*)"$/
     */
    public function iHaveTheSite($siteName)
    {
        $site = new \TYPO3\Neos\Domain\Model\Site($siteName);
        $site->setSiteResourcesPackageKey('TYPO3.NeosDemoTypo3Org');
        /** @var \TYPO3\Neos\Domain\Repository\SiteRepository $siteRepository */
        $siteRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\SiteRepository');
        $siteRepository->add($site);

        $this->getSubContext('flow')->persistAll();
    }

    /**
     * @When /^I export the site "([^"]*)"$/
     */
    public function iExportTheSite($siteNodeName)
    {
        /** @var \TYPO3\Neos\Domain\Service\SiteExportService $siteExportService */
        $siteExportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteExportService');

        /** @var \TYPO3\Neos\Domain\Repository\SiteRepository $siteRepository */
        $siteRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\SiteRepository');
        $site = $siteRepository->findOneByNodeName($siteNodeName);

        $this->lastExportedSiteXmlPathAndFilename = tempnam(sys_get_temp_dir(), 'Neos_LastExportedSite');

        file_put_contents($this->lastExportedSiteXmlPathAndFilename, $siteExportService->export(array($site)));
    }

    /**
     * @When /^I prune all sites$/
     */
    public function iPruneAllSites()
    {
        /** @var \TYPO3\Neos\Domain\Service\SiteService $siteService */
        $siteService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteService');
        $siteService->pruneAll();

        $this->getSubContext('flow')->persistAll();
    }

    /**
     * @When /^I import the last exported site$/
     */
    public function iImportTheLastExportedSite()
    {
        // Persist any pending entity insertions (caused by lazy creation of live Workspace)
        // This is a workaround which should be solved by properly isolating all read-only steps
        $this->getSubContext('flow')->persistAll();
        $this->resetNodeInstances();

        /** @var \TYPO3\Neos\Domain\Service\SiteImportService $siteImportService */
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
        $siteImportService->importFromFile($this->lastExportedSiteXmlPathAndFilename);
    }
}
