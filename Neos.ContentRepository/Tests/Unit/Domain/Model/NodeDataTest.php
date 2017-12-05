<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\RepositoryInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Test case for the "NodeData" domain model
 */
class NodeDataTest extends UnitTestCase
{
    /**
     * @var Workspace|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockWorkspace;

    /**
     * @var NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockNodeTypeManager;

    /**
     * @var NodeType
     */
    protected $mockNodeType;

    /**
     * @var NodeDataRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockNodeDataRepository;

    /**
     * @var NodeData|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodeData;

    public function setUp()
    {
        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->nodeData = $this->getAccessibleMock(NodeData::class, array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));

        $this->mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $this->mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($this->mockNodeType));
        $this->mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(true));
        $this->inject($this->nodeData, 'nodeTypeManager', $this->mockNodeTypeManager);

        $this->mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->nodeData, 'nodeDataRepository', $this->mockNodeDataRepository);
    }

    /**
     * @test
     */
    public function constructorSetsPathWorkspaceAndIdentifier()
    {
        $node = new NodeData('/foo/bar', $this->mockWorkspace, '12345abcde');
        $this->assertSame('/foo/bar', $node->getPath());
        $this->assertSame('bar', $node->getName());
        $this->assertSame($this->mockWorkspace, $node->getWorkspace());
        $this->assertSame('12345abcde', $node->getIdentifier());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidPaths()
     */
    public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed($path)
    {
        $this->nodeData->_call('setPath', $path, false);
    }

    /**
     */
    public function invalidPaths()
    {
        return array(
            array('foo'),
            array('/ '),
            array('//'),
            array('/foo//bar'),
            array('/foo/ bar'),
            array('/foo/bar/'),
            array('/123 bar'),
        );
    }

    /**
     * @test
     * @dataProvider validPaths()
     */
    public function setPathAcceptsAValidPath($path)
    {
        $this->nodeData->_call('setPath', $path, false);
        // dummy assertion to avoid PHPUnit warning in strict mode
        $this->assertTrue(true);
    }

    /**
     */
    public function validPaths()
    {
        return array(
            array('/foo'),
            array('/foo/bar'),
            array('/foo/bar/baz'),
            array('/12/foo'),
            array('/12356'),
            array('/foo-bar'),
            array('/foo-bar/1-5'),
            array('/foo-bar/bar/asdkak/dsflasdlfkjasd/asdflnasldfkjalsd/134-111324823-234234-234/sdasdflkj'),
        );
    }

    /**
     * @test
     */
    public function getDepthReturnsThePathDepthOfTheNode()
    {
        $node = new NodeData('/', $this->mockWorkspace);
        $this->assertEquals(0, $node->getDepth());

        $node = new NodeData('/foo', $this->mockWorkspace);
        $this->assertEquals(1, $node->getDepth());

        $node = new NodeData('/foo/bar', $this->mockWorkspace);
        $this->assertEquals(2, $node->getDepth());

        $node = new NodeData('/foo/bar/baz/quux', $this->mockWorkspace);
        $this->assertEquals(4, $node->getDepth());
    }

    /**
     * @test
     */
    public function setWorkspacesAllowsForSettingTheWorkspaceForInternalPurposes()
    {
        /** @var Workspace|\PHPUnit_Framework_MockObject_MockObject $newWorkspace */
        $newWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->assertSame($this->mockWorkspace, $this->nodeData->getWorkspace());

        $this->nodeData->setWorkspace($newWorkspace);
        $this->assertSame($newWorkspace, $this->nodeData->getWorkspace());
    }

    /**
     * @test
     */
    public function theIndexCanBeSetAndRetrieved()
    {
        $this->nodeData->setIndex(2);
        $this->assertEquals(2, $this->nodeData->getIndex());
    }

    /**
     * @test
     */
    public function getParentReturnsNullForARootNode()
    {
        $node = new NodeData('/', $this->mockWorkspace);
        $this->assertNull($node->getParent());
    }

    /**
     * @test
     */
    public function aContentObjectCanBeSetRetrievedAndUnset()
    {
        $contentObject = new \stdClass();

        $this->nodeData->setContentObject($contentObject);
        $this->assertSame($contentObject, $this->nodeData->getContentObject());

        $this->nodeData->unsetContentObject();
        $this->assertNull($this->nodeData->getContentObject());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function aContentObjectMustBeAnObject()
    {
        $this->nodeData->setContentObject('not an object');
    }

    /**
     * @test
     */
    public function propertiesCanBeSetAndRetrieved()
    {
        $this->nodeData->setProperty('title', 'My Title');
        $this->nodeData->setProperty('body', 'My Body');

        $this->assertTrue($this->nodeData->hasProperty('title'));
        $this->assertFalse($this->nodeData->hasProperty('iltfh'));

        $this->assertEquals('My Body', $this->nodeData->getProperty('body'));
        $this->assertEquals('My Title', $this->nodeData->getProperty('title'));

        $this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->nodeData->getProperties());

        $actualPropertyNames = $this->nodeData->getPropertyNames();
        sort($actualPropertyNames);
        $this->assertEquals(array('body', 'title'), $actualPropertyNames);
    }

    /**
     * @test
     */
    public function propertiesCanBeRemoved()
    {
        $this->nodeData->setProperty('title', 'My Title');
        $this->assertTrue($this->nodeData->hasProperty('title'));

        $this->nodeData->removeProperty('title');

        $this->assertFalse($this->nodeData->hasProperty('title'));

        $this->assertNotContains('title', $this->nodeData->getPropertyNames());

        $this->assertArrayNotHasKey('title', $this->nodeData->getProperties());
    }

    /**
     * @test
     */
    public function propertiesHandlesNullValuesCorrectly()
    {
        $this->nodeData->setProperty('value', null);

        $this->assertTrue($this->nodeData->hasProperty('value'));

        $this->assertNull($this->nodeData->getProperty('value'));

        $this->assertContains('value', $this->nodeData->getPropertyNames());

        $this->assertArrayHasKey('value', $this->nodeData->getProperties());

        $this->nodeData->removeProperty('value');

        $this->assertFalse($this->nodeData->hasProperty('value'));

        $this->assertNotContains('value', $this->nodeData->getPropertyNames());

        $this->assertArrayNotHasKey('value', $this->nodeData->getProperties());
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeException
     */
    public function removePropertyThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->nodeData->removeProperty('nada');
    }

    /**
     * @test
     */
    public function removePropertyDoesNotTouchAContentObject()
    {
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock(PersistenceManagerInterface::class));

        $className = uniqid('Test');
        eval('class ' .$className . ' {
				public $title = "My Title";
			}');
        $contentObject = new $className();
        $this->nodeData->setContentObject($contentObject);

        $this->nodeData->removeProperty('title');

        $this->assertTrue($this->nodeData->hasProperty('title'));
        $this->assertEquals('My Title', $this->nodeData->getProperty('title'));
    }

    /**
     * @test
     */
    public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined()
    {
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock(PersistenceManagerInterface::class));

        $className = uniqid('Test');
        eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
        $contentObject = new $className;

        $this->nodeData->setContentObject($contentObject);

        $this->assertTrue($this->nodeData->hasProperty('title'));
        $this->assertFalse($this->nodeData->hasProperty('iltfh'));

        $this->assertEquals('My Body', $this->nodeData->getProperty('body'));
        $this->assertEquals('My Title', $this->nodeData->getProperty('title'));

        $this->assertEquals(array('title' => 'My Title', 'body' => 'My Body'), $this->nodeData->getProperties());

        $actualPropertyNames = $this->nodeData->getPropertyNames();
        sort($actualPropertyNames);
        $this->assertEquals(array('body', 'title'), $actualPropertyNames);

        $this->nodeData->setProperty('title', 'My Other Title');
        $this->nodeData->setProperty('body', 'My Other Body');

        $this->assertEquals('My Other Body', $this->nodeData->getProperty('body'));
        $this->assertEquals('My Other Title', $this->nodeData->getProperty('title'));
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeException
     */
    public function getPropertyThrowsAnExceptionIfTheSpecifiedPropertyDoesNotExistInTheContentObject()
    {
        $className = uniqid('Test');
        eval('
			class ' .$className . ' {
				public $title = "My Title";
			}
		');
        $contentObject = new $className;
        $this->nodeData->setContentObject($contentObject);

        $this->nodeData->getProperty('foo');
    }

    /**
     * @test
     */
    public function theNodeTypeCanBeSetAndRetrieved()
    {
        /** @var NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject $mockNodeTypeManager */
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(true));
        $mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnCallback(
            function ($name) {
                return new NodeType($name, array(), array()) ;
            }
        ));

        $this->inject($this->nodeData, 'nodeTypeManager', $mockNodeTypeManager);

        $this->assertEquals('unstructured', $this->nodeData->getNodeType()->getName());

        $myNodeType = $mockNodeTypeManager->getNodeType('typo3:mycontent');
        $this->nodeData->setNodeType($myNodeType);
        $this->assertEquals($myNodeType, $this->nodeData->getNodeType());
    }

    /**
     * @test
     */
    public function getNodeTypeReturnsFallbackNodeTypeForUnknownNodeType()
    {
        $mockFallbackNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $mockNonExistingNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNonExistingNodeType->expects($this->atLeastOnce())->method('getName')->willReturn('definitelyNotAvailableNodeType');

        /** @var NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject $mockNodeTypeManager */
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects($this->atLeastOnce())->method('getNodeType')->with('definitelyNotAvailableNodeType')->will($this->returnValue($mockFallbackNodeType));
        $this->inject($this->nodeData, 'nodeTypeManager', $mockNodeTypeManager);
        $this->inject($this->nodeData, 'nodeType', $mockNonExistingNodeType);

        $this->assertSame($mockFallbackNodeType, $this->nodeData->getNodeType());
    }

    /**
     * @test
     */
    public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace()
    {
        $this->marktestIncomplete('Should be refactored to a contextualized node test.');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('countByParentAndNodeType', 'add'))->getMock();
        $nodeDataRepository->expects($this->once())->method('countByParentAndNodeType')->with('/', null, $this->mockWorkspace)->will($this->returnValue(0));
        $nodeDataRepository->expects($this->once())->method('add');
        $nodeDataRepository->expects($this->any())->method('getContext')->will($this->returnValue($context));

        /** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
        $currentNode = $this->getAccessibleMock(NodeData::class, array('getNode'), array('/', $this->mockWorkspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $currentNode->expects($this->once())->method('createProxyForContextIfNeeded')->will($this->returnArgument(0));
        $currentNode->expects($this->once())->method('filterNodeByContext')->will($this->returnArgument(0));

        $newNode = $currentNode->createNode('foo', 'mynodetype');
        $this->assertSame($currentNode, $newNode->getParent());
        $this->assertEquals(1, $newNode->getIndex());
        $this->assertEquals('mynodetype', $newNode->getNodeType()->getName());
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\NodeException
     */
    public function createNodeThrowsNodeExceptionIfPathAlreadyExists()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $oldNode = $this->getAccessibleMock(NodeData::class, array(), array('/foo', $this->mockWorkspace));

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByPath', 'getContext'))->getMock();
        $nodeDataRepository->expects($this->any())->method('findOneByPath')->with('/foo', $this->mockWorkspace)->will($this->returnValue($oldNode));

        /** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
        $currentNode = $this->getAccessibleMock(NodeData::class, array('getNode'), array('/', $this->mockWorkspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->_set('context', $mockContext);

        $currentNode->createNodeData('foo');
    }

    /**
     * @ test
     */
    public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist()
    {
        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByPath', 'getContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will($this->returnValue(null));

        $currentNode = $this->getAccessibleMock(NodeData::class, array('normalizePath', 'getContext'), array('/foo/baz', $this->mockWorkspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

        $this->assertNull($currentNode->getNode('/foo/quux'));
    }

    /**
     * @test
     */
    public function getChildNodeDataFindsUnreducedNodeDataChildren()
    {
        $childNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(array('/foo/bar', $this->mockWorkspace))->getMock();
        $nodeType = $this->getMockBuilder(NodeType::class)->setConstructorArgs(array('mynodetype', array(), array()))->getMock();
        $childNodeData->setNodeType($nodeType);
        $childNodeDataResults = array(
            $childNodeData
        );

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $nodeDataRepository->expects($this->at(0))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue($childNodeDataResults));
        $nodeDataRepository->expects($this->at(1))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue(array()));

        $nodeData = $this->getAccessibleMock(NodeData::class, array('dummy'), array('/foo', $this->mockWorkspace));
        $this->inject($nodeData, 'nodeDataRepository', $nodeDataRepository);

        $this->assertSame($childNodeDataResults, $nodeData->_call('getChildNodeData', 'mynodetype'));
        $this->assertSame(array(), $nodeData->_call('getChildNodeData', 'notexistingnodetype'));
    }


    /**
     * @test
     */
    public function removeFlagsTheNodeAsRemoved()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $workspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $nodeDataRepository = $this->getAccessibleMock(NodeDataRepository::class, array('setRemoved', 'update', 'remove'), array(), '', false);
        $this->inject($nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock(NodeData::class, array('addOrUpdate'), array('/foo', $workspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects($this->never())->method('remove');

        $currentNode->remove();

        $this->assertTrue($currentNode->isRemoved());
    }

    /**
     * @test
     */
    public function removeRemovesTheNodeFromRepositoryIfItsWorkspaceHasNoOtherBaseWorkspace()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $workspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(null));

        $nodeDataRepository = $this->getAccessibleMock(NodeDataRepository::class, array('remove', 'update'), array(), '', false);
        $this->inject($nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock(NodeData::class, null, array('/foo', $workspace));
        $this->inject($currentNode, 'persistenceManager', $mockPersistenceManager);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects($this->once())->method('remove');
        $mockPersistenceManager->expects($this->once())->method('isNewObject')->with($currentNode)->willReturn(false);

        $currentNode->remove();
    }

    /**
     * @return array
     */
    public function hasAccessRestrictionsDataProvider()
    {
        return array(
            array('accessRoles' => null, 'expectedResult' => false),
            array('accessRoles' => array(), 'expectedResult' => false),
            array('accessRoles' => array('Neos.Flow:Everybody'), 'expectedResult' => false),

            array('accessRoles' => array('Some.Other:Role'), 'expectedResult' => true),
            array('accessRoles' => array('Neos.Flow:Everybody', 'Some.Other:Role'), 'expectedResult' => true),
        );
    }

    /**
     * @param array $accessRoles
     * @param boolean $expectedResult
     * @test
     * @dataProvider hasAccessRestrictionsDataProvider
     */
    public function hasAccessRestrictionsTests($accessRoles, $expectedResult)
    {
        $this->nodeData->_set('accessRoles', $accessRoles);
        if ($expectedResult === true) {
            $this->assertTrue($this->nodeData->hasAccessRestrictions());
        } else {
            $this->assertFalse($this->nodeData->hasAccessRestrictions());
        }
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfAccessRolesIsNotSet()
    {
        $this->assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfSecurityContextCannotBeInitialized()
    {
        /** @var SecurityContext|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects($this->once(0))->method('canBeInitialized')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->never())->method('hasRole');
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(array('SomeOtherRole'));
        $this->assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsFalseIfAccessRolesIsSetAndSecurityContextHasNoRoles()
    {
        /** @var SecurityContext|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnValue(false));
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(array('SomeRole'));
        $this->assertFalse($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfAccessRolesIsSetAndSecurityContextHasOneOfTheRequiredRoles()
    {
        /** @var SecurityContext|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects($this->at(0))->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->at(1))->method('hasRole')->with('SomeRole')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->at(2))->method('hasRole')->with('SomeOtherRole')->will($this->returnValue(true));
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(array('SomeRole', 'SomeOtherRole'));
        $this->assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfRoleIsEveryone()
    {
        /** @var SecurityContext|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects($this->at(0))->method('canBeInitialized')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->at(1))->method('hasRole')->with('SomeRole')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->at(2))->method('hasRole')->with('Everyone')->will($this->returnValue(true));
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(array('SomeRole', 'Everyone', 'SomeOtherRole'));
        $this->assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function createNodeCreatesNodeDataWithExplicitWorkspaceIfGiven()
    {
        /** @var NodeDataRepository|\PHPUnit_Framework_MockObject_MockObject $nodeDataRepository */
        $nodeDataRepository = $this->createMock(NodeDataRepository::class);
        $this->inject($this->nodeData, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects($this->atLeastOnce())->method('setNewIndex');

        $this->nodeData->createNodeData('foo', null, null, $this->mockWorkspace);
    }

    /**
     * @test
     */
    public function similarizeClearsPropertiesBeforeAddingNewOnes()
    {
        /** @var $sourceNode NodeData */
        $sourceNode = $this->getAccessibleMock(NodeData::class, array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));
        $this->inject($sourceNode, 'nodeTypeManager', $this->mockNodeTypeManager);
        $sourceNode->_set('nodeDataRepository', $this->createMock(RepositoryInterface::class));

        $this->nodeData->setProperty('someProperty', 'somePropertyValue');
        $this->nodeData->setProperty('someOtherProperty', 'someOtherPropertyValue');

        $sourceNode->setProperty('newProperty', 'newPropertyValue');
        $sourceNode->setProperty('someProperty', 'someOverriddenPropertyValue');
        $this->nodeData->similarize($sourceNode);

        $expectedProperties = array(
            'newProperty' => 'newPropertyValue',
            'someProperty' => 'someOverriddenPropertyValue'
        );
        $this->assertEquals($expectedProperties, $this->nodeData->getProperties());
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithDifferentWorkspaceReturnsFalse()
    {
        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        /** @var Workspace|\PHPUnit_Framework_MockObject_MockObject $otherWorkspace */
        $otherWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $otherWorkspace->expects($this->any())->method('getName')->will($this->returnValue('other'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($otherWorkspace, null);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithDifferentDimensionReturnsFalse()
    {
        $this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('language' => array('en_US')));

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, array('language' => array('de_DE', 'mul_ZZ')));
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithMatchingWorkspaceAndDimensionsReturnsTrue()
    {
        $this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('language' => array('mul_ZZ')));

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, array('language' => array('de_DE', 'mul_ZZ')));
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function getDimensionValuesReturnsDimensionsSortedByKey()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('c' => array('c1', 'c2'), 'a' => array('a1')));
        $dimensionValues = $nodeData->getDimensionValues();

        $this->assertSame(array('a' => array('a1'), 'c' => array('c1', 'c2')), $dimensionValues);
    }

    /**
     * @test
     */
    public function dimensionsHashIsOrderIndependent()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('c' => array('c1', 'c2'), 'a' => array('a1')));
        $dimensionsHash = $nodeData->getDimensionsHash();

        $this->assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);

        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('a' => array('a1'), 'c' => array('c2', 'c1')));
        $dimensionsHash = $nodeData->getDimensionsHash();

        $this->assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);
    }

    /**
     * @test
     */
    public function setDimensionsAddsDimensionValues()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace);

        $dimensionsToSet = array(
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'c', 'c2'),
            new NodeDimension($nodeData, 'a', 'a1'),
            new NodeDimension($nodeData, 'b', 'b1')
        );
        $expectedDimensionValues = array(
            'a' => array('a1'),
            'b' => array('b1'),
            'c' => array('c1', 'c2')
        );

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        $this->assertSame($expectedDimensionValues, $setDimensionValues);
    }

    /**
     * @test
     */
    public function setDimensionsAddsNewDimensionValues()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('c' => array('c1', 'c2'), 'a' => array('a1')));

        $dimensionsToSet = array(
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'c', 'c2'),
            new NodeDimension($nodeData, 'a', 'a1'),
            new NodeDimension($nodeData, 'b', 'b1')
        );
        $expectedDimensionValues = array(
            'a' => array('a1'),
            'b' => array('b1'),
            'c' => array('c1', 'c2')
        );

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        $this->assertSame($expectedDimensionValues, $setDimensionValues);
    }

    /**
     * @test
     */
    public function setDimensionsRemovesDimensionValuesNotGiven()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, array('c' => array('c1', 'c2'), 'a' => array('a1')));

        $dimensionsToSet = array(
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'b', 'b1'),
            new NodeDimension($nodeData, 'f', 'f1')
        );
        $expectedDimensionValues = array(
            'b' => array('b1'),
            'c' => array('c1'),
            'f' => array('f1')
        );

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        $this->assertSame($expectedDimensionValues, $setDimensionValues);
    }
}
