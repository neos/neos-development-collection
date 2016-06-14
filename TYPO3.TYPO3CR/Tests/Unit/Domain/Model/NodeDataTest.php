<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeDimension;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

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
        $this->mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $this->nodeData = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));

        $this->mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();

        $this->mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
        $this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($this->mockNodeType));
        $this->mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(true));
        $this->inject($this->nodeData, 'nodeTypeManager', $this->mockNodeTypeManager);

        $this->mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
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
        $newWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

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
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
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
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

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
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface'));

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
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
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
        $mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
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
        $mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
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

        $context = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $context->expects($this->once())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('countByParentAndNodeType', 'add'))->getMock();
        $nodeDataRepository->expects($this->once())->method('countByParentAndNodeType')->with('/', null, $this->mockWorkspace)->will($this->returnValue(0));
        $nodeDataRepository->expects($this->once())->method('add');
        $nodeDataRepository->expects($this->any())->method('getContext')->will($this->returnValue($context));

        /** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
        $currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
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
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeException
     */
    public function createNodeThrowsNodeExceptionIfPathAlreadyExists()
    {
        $mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $oldNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array(), array('/foo', $this->mockWorkspace));

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByPath', 'getContext'))->getMock();
        $nodeDataRepository->expects($this->any())->method('findOneByPath')->with('/foo', $this->mockWorkspace)->will($this->returnValue($oldNode));

        /** @var NodeData|\PHPUnit_Framework_MockObject_MockObject $currentNode */
        $currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('getNode'), array('/', $this->mockWorkspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->_set('context', $mockContext);

        $currentNode->createNodeData('foo');
    }

    /**
     * @test
     */
    public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist()
    {
        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->setMethods(array('findOneByPath', 'getContext'))->getMock();
        $nodeDataRepository->expects($this->once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will($this->returnValue(null));

        $currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('normalizePath', 'getContext'), array('/foo/baz', $this->mockWorkspace));
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->expects($this->once())->method('normalizePath')->with('/foo/quux')->will($this->returnValue('/foo/quux'));

        $this->assertNull($currentNode->getNode('/foo/quux'));
    }

    /**
     * @test
     */
    public function getChildNodeDataFindsUnreducedNodeDataChildren()
    {
        $childNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->setConstructorArgs(array('/foo/bar', $this->mockWorkspace))->getMock();
        $nodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->setConstructorArgs(array('mynodetype', array(), array()))->getMock();
        $childNodeData->setNodeType($nodeType);
        $childNodeDataResults = array(
            $childNodeData
        );

        $nodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
        $nodeDataRepository->expects($this->at(0))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue($childNodeDataResults));
        $nodeDataRepository->expects($this->at(1))->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->will($this->returnValue(array()));

        $nodeData = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array('/foo', $this->mockWorkspace));
        $this->inject($nodeData, 'nodeDataRepository', $nodeDataRepository);

        $this->assertSame($childNodeDataResults, $nodeData->_call('getChildNodeData', 'mynodetype'));
        $this->assertSame(array(), $nodeData->_call('getChildNodeData', 'notexistingnodetype'));
    }


    /**
     * @test
     */
    public function removeFlagsTheNodeAsRemoved()
    {
        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

        $workspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $nodeDataRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('setRemoved', 'update', 'remove'), array(), '', false);
        $this->inject($nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo', $workspace));
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
        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');

        $workspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $workspace->expects($this->once())->method('getBaseWorkspace')->will($this->returnValue(null));

        $nodeDataRepository = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', array('remove', 'update'), array(), '', false);
        $this->inject($nodeDataRepository, 'entityClassName', 'TYPO3\TYPO3CR\Domain\Model\NodeData');
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', null, array('/foo', $workspace));
        $this->inject($currentNode, 'persistenceManager', $mockPersistenceManager);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects($this->once())->method('remove');
        $mockPersistenceManager->expects($this->once())->method('isNewObject')->with($currentNode)->willReturn(false);

        $currentNode->remove();
    }

    /**
     * @param string $currentPath
     * @param string $relativePath
     * @param string $normalizedPath
     * @test
     * @dataProvider abnormalPaths
     */
    public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath)
    {
        $this->nodeData->_set('path', $currentPath);
        $this->assertSame($normalizedPath, $this->nodeData->_call('normalizePath', $relativePath));
    }

    /**
     * @return array
     */
    public function abnormalPaths()
    {
        return array(
            array('/', '/', '/'),
            array('/', '/.', '/'),
            array('/', '.', '/'),
            array('/', 'foo/bar', '/foo/bar'),
            array('/foo', '.', '/foo'),
            array('/foo', '/foo/.', '/foo'),
            array('/foo', '../', '/'),
            array('/foo/bar', '../baz', '/foo/baz'),
            array('/foo/bar', '../baz/../bar', '/foo/bar'),
            array('/foo/bar', '.././..', '/'),
            array('/foo/bar', '../../.', '/'),
            array('/foo/bar/baz', '../..', '/foo'),
            array('/foo/bar/baz', '../quux', '/foo/bar/quux'),
            array('/foo/bar/baz', '../quux/.', '/foo/bar/quux')
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash()
    {
        $node = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('dummy'), array(), '', false);
        $node->_call('normalizePath', 'foo//bar');
    }

    /**
     * @return array
     */
    public function hasAccessRestrictionsDataProvider()
    {
        return array(
            array('accessRoles' => null, 'expectedResult' => false),
            array('accessRoles' => array(), 'expectedResult' => false),
            array('accessRoles' => array('TYPO3.Flow:Everybody'), 'expectedResult' => false),

            array('accessRoles' => array('Some.Other:Role'), 'expectedResult' => true),
            array('accessRoles' => array('TYPO3.Flow:Everybody', 'Some.Other:Role'), 'expectedResult' => true),
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
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
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
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
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
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
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
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
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
        $nodeDataRepository = $this->createMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
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
        $sourceNode = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeData', array('addOrUpdate'), array('/foo/bar', $this->mockWorkspace));
        $this->inject($sourceNode, 'nodeTypeManager', $this->mockNodeTypeManager);
        $sourceNode->_set('nodeDataRepository', $this->createMock('TYPO3\Flow\Persistence\RepositoryInterface'));

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
        $otherWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
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
