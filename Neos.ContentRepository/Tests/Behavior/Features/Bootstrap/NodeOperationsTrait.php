<?php
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Service\PublishingServiceInterface;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\Functional\Command\TableNode;
use Neos\Utility\Arrays;
use PHPUnit\Framework\Assert as Assert;
use Symfony\Component\Yaml\Yaml;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires that the Flow Object Manager must be available via $this->getObjectManager().
 *
 * Note: This trait expects the IsolatedBehatStepsTrait to be available!
 */
trait NodeOperationsTrait
{
    /**
     * @var array<\Neos\ContentRepository\Domain\Model\NodeInterface>
     */
    private $currentNodes = [];

    /**
     * @var array
     */
    private $nodeTypesConfiguration = [];

    /**
     * @return mixed
     */
    abstract protected function getObjectManager(): ObjectManagerInterface;

    /**
     * @return PublishingServiceInterface
     */
    private function getPublishingService()
    {
        return $this->getObjectManager()->get(PublishingServiceInterface::class);
    }

    /**
     * @return PersistenceManagerInterface
     */
    private function getPersistenceManager()
    {
        return $this->getObjectManager()->get(PersistenceManagerInterface::class);
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
     * @Given /^I have the following nodes:$/
     * @When /^I create the following nodes:$/
     */
    public function iHaveTheFollowingNodes($table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))), true);
        } else {
            /** @var \Neos\ContentRepository\Domain\Service\NodeTypeManager $nodeTypeManager */
            $nodeTypeManager = $this->getObjectManager()->get(NodeTypeManager::class);
            $rows = $table->getHash();
            foreach ($rows as $row) {
                $path = $row['Path'];
                $name = implode('', array_slice(explode('/', $path), -1, 1));
                $parentPath = implode('/', array_slice(explode('/', $path), 0, -1)) ? : '/';

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

                if (isset($row['Hidden']) && $row['Hidden'] === 'true') {
                    $hidden = true;
                } else {
                    $hidden = false;
                }

                $parentNode = $context->getNode($parentPath);
                if ($parentNode === null) {
                    throw new \Exception(sprintf('Could not get parent node with path %s to create node %s', $parentPath, $path));
                }

                $node = $parentNode->createNode($name, $nodeType, $identifier);

                if (isset($row['Properties']) && $row['Properties'] !== '') {
                    $properties = json_decode($row['Properties'], true);
                    if ($properties === null) {
                        throw new \Exception(sprintf('Error decoding json value "%s": %d', $row['Properties'], json_last_error()));
                    }
                    foreach ($properties as $propertyName => $propertyValue) {
                        $node->setProperty($propertyName, $propertyValue);
                    }
                }

                $node->setHidden($hidden);
            }

            // Make sure we do not use cached instances
            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Given /^I have the following workspaces:$/
     */
    public function iHaveTheFollowingWorkspaces($table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))), true);
        } else {
            $rows = $table->getHash();
            $workspaceRepository = $this->getObjectManager()->get(WorkspaceRepository::class);
            foreach ($rows as $row) {
                $name = $row['Name'];
                $baseWorkspaceName = $row['Base Workspace'];

                $baseWorkspace = $workspaceRepository->findOneByName($baseWorkspaceName);
                $workspace = new Workspace($name, $baseWorkspace);
                $workspaceRepository->add($workspace);
                $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            }
        }
    }

    /**
     * @Given /^I have the following content dimensions:$/
     */
    public function iHaveTheFollowingContentDimensions($table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $dimensions = [];
            $presetsFound = false;
            foreach ($table->getHash() as $row) {
                $dimensions[$row['Identifier']] = [
                    'default' => $row['Default']
                ];

                $defaultPreset = '';
                if (isset($row['Presets'])) {
                    $presetsFound = true;
                    // parse a preset string like:
                    // preset1=dimensionValue1,dimensionValue2; preset2=dimensionValue3
                    $presetStrings = Arrays::trimExplode(';', $row['Presets']);
                    $presets = [];
                    foreach ($presetStrings as $presetString) {
                        list($presetName, $presetValues) = Arrays::trimExplode('=', $presetString);
                        $presets[$presetName] = [
                            'values' => Arrays::trimExplode(',', $presetValues),
                            'uriSegment' => $presetName
                        ];

                        if ($defaultPreset === '') {
                            $defaultPreset = $presetName;
                        }
                    }

                    $dimensions[$row['Identifier']]['presets'] = $presets;
                    $dimensions[$row['Identifier']]['defaultPreset'] = $defaultPreset;
                }
            }
            $contentDimensionRepository = $this->getObjectManager()->get(ContentDimensionRepository::class);
            $contentDimensionRepository->setDimensionsConfiguration($dimensions);

            if ($presetsFound === true) {
                $contentDimensionPresetSource = $this->getObjectManager()->get(ContentDimensionPresetSourceInterface::class);
                $contentDimensionPresetSource->setConfiguration($dimensions);
            }
        }
    }

    /**
     * @When /^I copy the node (into|after|before) path "([^"]*)" with the following context:$/
     */
    public function iCopyANodeToPath($position, $path, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($position), 'string', escapeshellarg($path), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
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
            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @When /^I move the node (into|after|before) path "([^"]*)" with the following context:$/
     */
    public function iMoveANodeToPath($position, $path, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($position), 'string', escapeshellarg($path), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
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
    public function iGetANodeByPathWithTheFollowingContext($path, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($path), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            if ($context->getWorkspace() === null) {
                // FIXME: Adjust to changed getWorkspace() method -> workspace needs to be created in another way
                $context->getWorkspace(true);

                $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
                $this->resetNodeInstances();

                $context = $this->getContextForProperties($rows[0]);
            }

            $node = $context->getNode($path);
            if ($node !== null) {
                $this->currentNodes = [$node];
            } else {
                $this->currentNodes = [];
            }
        }
    }

    /**
     * @When /^I get a node by identifier "([^"]*)" with the following context:$/
     */
    public function iGetANodeByIdentifierWithTheFollowingContext($identifier, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($identifier), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            $node = $context->getNodeByIdentifier($identifier);
            if ($node !== null) {
                $this->currentNodes = [$node];
            } else {
                $this->currentNodes = [];
            }
        }
    }

    /**
     * @When /^I get the child nodes of "([^"]*)" with the following context:$/
     */
    public function iGetTheChildNodesOfWithTheFollowingContext($path, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($path), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
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
    public function iGetTheChildNodesOfWithFilterAndTheFollowingContext($path, $filter, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($path), 'string', escapeshellarg($filter), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
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
    public function iGetTheNodesOnPathToWithTheFollowingContext($startingPoint, $endPoint, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s', 'string', escapeshellarg($startingPoint), 'string', escapeshellarg($endPoint), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            $this->currentNodes = $context->getNodesOnPath($startingPoint, $endPoint);
        }
    }

    /**
     * @When /^I run getNode with the path "([^"]*)" on the current node$/
     */
    public function iRunGetNodeWithThePathOnTheCurrentNode($path)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $node = $this->iShouldHaveOneNode();
            $retrievedNode = $node->getNode($path);
            $this->currentNodes = $retrievedNode ? [ $retrievedNode ] : [];
        }
    }

    /**
     * @When /^I retrieve the current site node$/
     */
    public function iRetrieveTheCurrentSiteNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $node = $this->iShouldHaveOneNode();
            $retrievedNode = $node->getContext()->getCurrentSiteNode();
            $this->currentNodes = $retrievedNode ? [ $retrievedNode ] : [];
        }
    }

    /**
     * @When /^I publish the node$/
     */
    public function iPublishTheNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $node = $this->iShouldHaveOneNode();

            $publishingService = $this->getPublishingService();
            $publishingService->publishNode($node);

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @When /^I publish the workspace "([^"]*)"$/
     */
    public function iPublishTheWorkspace($sourceWorkspaceName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($sourceWorkspaceName)));
        } else {

            /**
             * FIXME: Workspace properties from the previous workspace
             * like fallback dimensions are not available from this point forward.
             * The method ``iPublishTheNode`` keeps all this information intact.
             **/

            $sourceContext = $this->getContextForProperties(['Workspace' => $sourceWorkspaceName]);
            $sourceWorkspace = $sourceContext->getWorkspace();

            $sourceWorkspace->publish($sourceWorkspace->getBaseWorkspace());

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @When /^I discard all changes in the workspace "([^"]*)"$/
     */
    public function iDiscardTheWorkspace($workspaceName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($workspaceName)));
        } else {
            $context = $this->getContextForProperties(['Workspace' => $workspaceName]);
            $workspace = $context->getWorkspace();

            /** @var PublishingServiceInterface $publishingService */
            $publishingService = $this->getObjectManager()->get(\Neos\ContentRepository\Domain\Service\PublishingServiceInterface::class);
            $publishingService->discardNodes($publishingService->getUnpublishedNodes($workspace));

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @When /^I discard the node$/
     */
    public function iDiscardTheNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $node = $this->iShouldHaveOneNode();

            $publishingService = $this->getPublishingService();
            $publishingService->discardNode($node);

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @When /^I use the publishing service to publish nodes in the workspace "([^"]*)" with the following context:$/
     */
    public function iUseThePublishingServiceToPublishNodesInTheWorkspace($sourceWorkspaceName, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($sourceWorkspaceName), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            /** @var PublishingServiceInterface $publishingService */
            $publishingService = $this->getObjectManager()->get(PublishingServiceInterface::class);

            $rows = $table->getHash();
            $rows[0]['Workspace'] = $sourceWorkspaceName;

            $sourceContext = $this->getContextForProperties($rows[0]);
            $sourceWorkspace = $sourceContext->getWorkspace();

            $publishingService->publishNodes($publishingService->getUnpublishedNodes($sourceWorkspace));

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Then /^I should (not |)be able to rename the node to "([^"]*)"$/
     */
    public function iShouldBeAbleToRenameTheNodeTo($not, $newName)
    {
        if ($this->isolated === true) {
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
    public function iRemoveTheNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $node = $this->iShouldHaveOneNode();
            $node->remove();

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Given /^I set some property and rename the node to "([^"]*)"$/
     */
    public function iSetSomePropertyAndRenameTheNodeTo($newName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($newName)));
        } else {
            $node = $this->iShouldHaveOneNode();
            $node->setHidden(null);
            $node->setName($newName);
        }
    }

    /**
     * @Given /^I rename the node to "([^"]*)"$/
     */
    public function iRenameTheNodeTo($newName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($newName)));
        } else {
            $node = $this->iShouldHaveOneNode();
            $node->setName($newName);
        }
    }

    /**
     * @Given /^I move the node (before|after|into) the node with path "([^"]*)"$/
     */
    public function iMoveTheNodeIntoTheNodeWithPath($action, $referenceNodePath)
    {
        if ($this->isolated === true) {
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

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Then /^I should have one node$/
     *
     * @return \Neos\ContentRepository\Domain\Model\NodeInterface
     */
    public function iShouldHaveOneNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            Assert::assertCount(1, $this->currentNodes);
            return $this->currentNodes[0];
        }
    }

    /**
     * @Then /^I should have (\d+) nodes$/
     */
    public function iShouldHaveNodes($count)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'integer', escapeshellarg($count)));
        } else {
            Assert::assertCount((integer)$count, $this->currentNodes);
        }
    }

    /**
     * @Then /^the node property "([^"]*)" should be "([^"]*)"$/
     */
    public function theNodePropertyShouldBe($propertyName, $propertyValue)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            Assert::assertEquals($propertyValue, $currentNode->getProperty($propertyName));
        }
    }

    /**
     * @Then /^the node should (not |)have a property "([^"]*)"$/
     */
    public function theNodeShouldHaveAProperty($not, $propertyName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            $expected = false;
            if (empty($not)) {
                $expected = true;
            }
            Assert::assertEquals($expected, $currentNode->hasProperty($propertyName));
        }
    }

    /**
     * @Then /^the node should be hidden in index$/
     */
    public function theNodeShouldBeHiddenInIndex()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            Assert::assertTrue($currentNode->isHiddenInIndex(), 'The current node should be hidden in index, but it is not.');
        }
    }

    /**
     * @When /^I set the node property "([^"]*)" to "([^"]*)"$/
     */
    public function iSetTheNodePropertyTo($propertyName, $propertyValue)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($propertyName), 'string', escapeshellarg($propertyValue)));
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            $currentNode->setProperty($propertyName, $propertyValue);

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Given /^I set the node name to "([^"]*)"$/
     */
    public function iSetTheNodeNameTo($name)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($name)));
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            $currentNode->setName($name);

            $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
            $this->resetNodeInstances();
        }
    }

    /**
     * @Then /^The node language dimension should be "([^"]*)"$/
     */
    public function theNodeLanguageShouldBe($language)
    {
        if ($this->isolated === true) {
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
    public function iShouldHaveANodeWithPathAndValueForPropertyForTheFollowingContext($path, $propertyValue, $propertyName, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg($path), 'string', escapeshellarg($propertyValue), 'string', escapeshellarg($propertyName), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $this->iGetANodeByPathWithTheFollowingContext($path, $table);
            $this->theNodePropertyShouldBe($propertyName, $propertyValue);
        }
    }

    /**
     * @When /^I adopt the node (recursively |)to the following context:$/
     */
    public function iAdoptTheNodeToTheFollowingContext($recursive, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($recursive), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            $currentNode = $this->iShouldHaveOneNode();
            $this->currentNodes = [$context->adoptNode($currentNode, $recursive !== '')];
        }
    }

    /**
     * @Then /^I should have the following nodes(| in any order):$/
     */
    public function iShouldHaveTheFollowingNodes($orderIndependent, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($orderIndependent), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
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
            } else {
                $currentNodes = $this->currentNodes;
                foreach ($currentNodes as $nodeIndex => $node) {
                    foreach ($rows as $rowIndex => $row) {
                        if (isset($row['Path']) && $row['Path'] !== $node->getPath()) {
                            continue;
                        }
                        if (isset($row['Properties'])) {
                            $nodeProperties = $node->getProperties();
                            $testProperties = json_decode($row['Properties'], true);
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
                Assert::assertEquals([], $rows, 'All examples should have matched');
                Assert::assertCount(0, $currentNodes, 'All nodes should be matched');
            }
        }
    }

    /**
     * @Then /^the unpublished node count in workspace "([^"]*)" should be (\d+)$/
     */
    public function theUnpublishedNodeCountInWorkspaceShouldBe($workspaceName, $count)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($workspaceName), 'integer', escapeshellarg($count)));
        } else {
            $workspaceRepository = $this->getObjectManager()->get(WorkspaceRepository::class);
            $workspace = $workspaceRepository->findOneByName($workspaceName);
            $publishingService = $this->getObjectManager()->get(PublishingServiceInterface::class);
            $unpublishedNodesCount = $publishingService->getUnpublishedNodesCount($workspace);
            Assert::assertEquals($count, $unpublishedNodesCount);
        }
    }

    /**
     * @Then /^print the nodes$/
     */
    public function printTheNodes()
    {
        if ($this->isolated === true) {
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
    public function resetCustomNodeTypes()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $this->getObjectManager()->get(NodeTypeManager::class)->overrideNodeTypes([]);
        }
    }

    /**
     * @Given /^I have the following (additional |)NodeTypes configuration:$/
     */
    public function iHaveTheFollowingNodetypesConfiguration($additional, $nodeTypesConfiguration)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($additional), 'integer', escapeshellarg($nodeTypesConfiguration)));
        } else {
            if (strlen($additional) > 0) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($this->nodeTypesConfiguration, Yaml::parse($nodeTypesConfiguration->getRaw()));
            } else {
                $this->nodeTypesConfiguration = Yaml::parse($nodeTypesConfiguration->getRaw());
                $configuration = $this->nodeTypesConfiguration;
            }
            $this->getObjectManager()->get(NodeTypeManager::class)->overrideNodeTypes($configuration);
        }
    }

    /**
     * @Then /^I should (not |)be able to create a child node of type "([^"]*)"$/
     */
    public function iShouldBeAbleToCreateAChildNodeOfType($not, $nodeTypeName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg(trim($not)), 'integer', escapeshellarg($nodeTypeName)));
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            $nodeType = $this->getObjectManager()->get(NodeTypeManager::class)->getNodeType($nodeTypeName);
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
                } catch (NodeConstraintException $nodeConstraintExceptio) {
                    // Expected exception
                }
            }
        }
    }

    /**
     * @When /^I get other node variants of the node$/
     */
    public function iGetOtherNodeVariantsOfTheNode()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $currentNode = $this->iShouldHaveOneNode();
            $this->currentNodes = $currentNode->getOtherNodeVariants();
        }
    }

    /**
     * @When /^I get node variants of "([^"]*)" with the following context:$/
     */
    public function iGetNodeVariantsOfWithTheFollowingContext($identifier, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($identifier), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            $this->currentNodes = $context->getNodeVariantsByIdentifier($identifier);
        }
    }

    /**
     * @Then /^I expect to have (\d+) unpublished node[s]? for the following context:$/
     */
    public function iExpectToHaveUnpublishedNodesForTheFollowingContext($nodeCount, $table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'integer', escapeshellarg($nodeCount), escapeshellarg(TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $rows = $table->getHash();
            $context = $this->getContextForProperties($rows[0]);

            $publishingService = $this->getPublishingService();
            Assert::assertEquals((int)$nodeCount, count($publishingService->getUnpublishedNodes($context->getWorkspace())));
        }
    }

    /**
     * @When /^I unhide the node$/
     */
    public function iMakeTheNodevisible()
    {
        $node = $this->iShouldHaveOneNode();
        $node->setHidden(false);

        $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
        $this->resetNodeInstances();
    }

    /**
     * @When /^I hide the node$/
     */
    public function iHideTheNode()
    {
        $node = $this->iShouldHaveOneNode();
        $node->setHidden(true);

        $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
        $this->resetNodeInstances();
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
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $this->objectManager->get(NodeDataRepository::class)->flushNodeRegistry();
            $this->objectManager->get(ContextFactoryInterface::class)->reset();
            $this->objectManager->get(NodeFactory::class)->reset();
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function resetContentDimensions()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $contentDimensionRepository = $this->getObjectManager()->get(ContentDimensionRepository::class);
            /** @var ContentDimensionRepository $contentDimensionRepository */

            // Set the content dimensions to a fixed value for Behat scenarios
            $contentDimensionRepository->setDimensionsConfiguration(['language' => ['default' => 'mul_ZZ']]);
        }
    }

    /**
     *
     *
     * @param array $humanReadableContextProperties
     * @param boolean $addDimensionDefaults
     * @return \Neos\ContentRepository\Domain\Service\Context
     * @throws Exception
     */
    protected function getContextForProperties(array $humanReadableContextProperties, $addDimensionDefaults = false)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($humanReadableContextProperties), 'integer', escapeshellarg($addDimensionDefaults)));
        } else {
            /** @var ContextFactoryInterface $contextFactory */
            $contextFactory = $this->getObjectManager()->get(ContextFactoryInterface::class);
            $contextProperties = [];

            if (isset($humanReadableContextProperties['Language'])) {
                $contextProperties['dimensions']['language'] = Arrays::trimExplode(',', $humanReadableContextProperties['Language']);
            }

            if (isset($humanReadableContextProperties['Workspace'])) {
                $contextProperties['workspaceName'] = $humanReadableContextProperties['Workspace'];
                $this->createWorkspaceIfNeeded($contextProperties['workspaceName']);
            } else {
                $this->createWorkspaceIfNeeded();
            }

            if (isset($humanReadableContextProperties['Hidden'])) {
                $contextProperties['hidden'] = $humanReadableContextProperties['Hidden'];
            }

            foreach ($humanReadableContextProperties as $propertyName => $propertyValue) {
                // Set flexible dimensions from features
                if (strpos($propertyName, 'Dimension: ') === 0) {
                    $contextProperties['dimensions'][substr($propertyName, strlen('Dimension: '))] = Arrays::trimExplode(',', $propertyValue);
                }

                // FIXME We lack a good API to manipulate dimension values explicitly or assign multiple values, so we are doing this via target dimension values
                if (strpos($propertyName, 'Target dimension: ') === 0) {
                    if ($propertyValue === '') {
                        $propertyValue = null;
                    }
                    $contextProperties['targetDimensions'][substr($propertyName, strlen('Target dimension: '))] = $propertyValue;
                }
            }

            if ($addDimensionDefaults) {
                $contentDimensionRepository = $this->getObjectManager()->get(ContentDimensionRepository::class);
                $availableDimensions = $contentDimensionRepository->findAll();
                foreach ($availableDimensions as $dimension) {
                    if (isset($contextProperties['dimensions'][$dimension->getIdentifier()]) && !in_array($dimension->getDefault(), $contextProperties['dimensions'][$dimension->getIdentifier()])) {
                        $contextProperties['dimensions'][$dimension->getIdentifier()][] = $dimension->getDefault();
                    }
                }
            }

            $contextProperties['invisibleContentShown'] = true;

            return $contextFactory->create($contextProperties);
        }
    }

    /**
     * Make sure that the "live" workspace and the requested $workspaceName workspace exist.
     *
     * @param string $workspaceName
     * @return void
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function createWorkspaceIfNeeded($workspaceName = null)
    {
        /** @var WorkspaceRepository $workspaceRepository */
        $workspaceRepository = $this->getObjectManager()->get(WorkspaceRepository::class);
        $liveWorkspace = $workspaceRepository->findOneByName('live');
        if ($liveWorkspace === null) {
            $liveWorkspace = new Workspace('live');
            $workspaceRepository->add($liveWorkspace);
            $this->getPersistenceManager()->persistAll();
            $this->resetNodeInstances();
        }

        if ($workspaceName !== null) {
            $requestedWorkspace = $workspaceRepository->findOneByName($workspaceName);
            if ($requestedWorkspace === null) {
                $requestedWorkspace = new Workspace($workspaceName, $liveWorkspace);
                $workspaceRepository->add($requestedWorkspace);
                $this->getPersistenceManager()->persistAll();
                $this->resetNodeInstances();
            }
        }
    }
}
