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

use Neos\ContentRepository\Exception\NodeException;
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
     * @var Workspace|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockWorkspace;

    /**
     * @var NodeTypeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockNodeTypeManager;

    /**
     * @var NodeType
     */
    protected $mockNodeType;

    /**
     * @var NodeDataRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockNodeDataRepository;

    /**
     * @var NodeData|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $nodeData;

    public function setUp(): void
    {
        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $this->nodeData = $this->getAccessibleMock(NodeData::class, ['addOrUpdate'], ['/foo/bar', $this->mockWorkspace]);

        $this->mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $this->mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $this->mockNodeTypeManager->expects(self::any())->method('getNodeType')->will(self::returnValue($this->mockNodeType));
        $this->mockNodeTypeManager->expects(self::any())->method('hasNodeType')->will(self::returnValue(true));
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
        self::assertSame('/foo/bar', $node->getPath());
        self::assertSame('bar', $node->getName());
        self::assertSame($this->mockWorkspace, $node->getWorkspace());
        self::assertSame('12345abcde', $node->getIdentifier());
    }

    /**
     * @test
     * @dataProvider invalidPaths()
     */
    public function setPathThrowsAnExceptionIfAnInvalidPathIsPassed($path)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->nodeData->_call('setPath', $path, false);
    }

    /**
     */
    public function invalidPaths()
    {
        return [
            ['foo'],
            ['/ '],
            ['//'],
            ['/foo//bar'],
            ['/foo/ bar'],
            ['/foo/bar/'],
            ['/123 bar'],
        ];
    }

    /**
     * @test
     * @dataProvider validPaths()
     */
    public function setPathAcceptsAValidPath($path)
    {
        $this->nodeData->_call('setPath', $path, false);
        // dummy assertion to avoid PHPUnit warning in strict mode
        self::assertTrue(true);
    }

    /**
     */
    public function validPaths()
    {
        return [
            ['/foo'],
            ['/foo/bar'],
            ['/foo/bar/baz'],
            ['/12/foo'],
            ['/12356'],
            ['/foo-bar'],
            ['/foo-bar/1-5'],
            ['/foo-bar/bar/asdkak/dsflasdlfkjasd/asdflnasldfkjalsd/134-111324823-234234-234/sdasdflkj'],
        ];
    }

    /**
     * @test
     */
    public function getDepthReturnsThePathDepthOfTheNode()
    {
        $node = new NodeData('/', $this->mockWorkspace);
        self::assertEquals(0, $node->getDepth());

        $node = new NodeData('/foo', $this->mockWorkspace);
        self::assertEquals(1, $node->getDepth());

        $node = new NodeData('/foo/bar', $this->mockWorkspace);
        self::assertEquals(2, $node->getDepth());

        $node = new NodeData('/foo/bar/baz/quux', $this->mockWorkspace);
        self::assertEquals(4, $node->getDepth());
    }

    /**
     * @test
     */
    public function setWorkspacesAllowsForSettingTheWorkspaceForInternalPurposes()
    {
        /** @var Workspace|\PHPUnit\Framework\MockObject\MockObject $newWorkspace */
        $newWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        self::assertSame($this->mockWorkspace, $this->nodeData->getWorkspace());

        $this->nodeData->setWorkspace($newWorkspace);
        self::assertSame($newWorkspace, $this->nodeData->getWorkspace());
    }

    /**
     * @test
     */
    public function theIndexCanBeSetAndRetrieved()
    {
        $this->nodeData->setIndex(2);
        self::assertEquals(2, $this->nodeData->getIndex());
    }

    /**
     * @test
     */
    public function getParentReturnsNullForARootNode()
    {
        $node = new NodeData('/', $this->mockWorkspace);
        self::assertNull($node->getParent());
    }

    /**
     * @test
     */
    public function aContentObjectCanBeSetRetrievedAndUnset()
    {
        $contentObject = new \stdClass();

        $this->nodeData->setContentObject($contentObject);
        self::assertSame($contentObject, $this->nodeData->getContentObject());

        $this->nodeData->unsetContentObject();
        self::assertNull($this->nodeData->getContentObject());
    }

    /**
     * @test
     */
    public function aContentObjectMustBeAnObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->nodeData->setContentObject('not an object');
    }

    /**
     * @test
     */
    public function propertiesCanBeSetAndRetrieved()
    {
        $this->nodeData->setProperty('title', 'My Title');
        $this->nodeData->setProperty('body', 'My Body');

        self::assertTrue($this->nodeData->hasProperty('title'));
        self::assertFalse($this->nodeData->hasProperty('iltfh'));

        self::assertEquals('My Body', $this->nodeData->getProperty('body'));
        self::assertEquals('My Title', $this->nodeData->getProperty('title'));

        self::assertEquals(['title' => 'My Title', 'body' => 'My Body'], $this->nodeData->getProperties());

        $actualPropertyNames = $this->nodeData->getPropertyNames();
        sort($actualPropertyNames);
        self::assertEquals(['body', 'title'], $actualPropertyNames);
    }

    /**
     * @test
     */
    public function propertiesCanBeRemoved()
    {
        $this->nodeData->setProperty('title', 'My Title');
        self::assertTrue($this->nodeData->hasProperty('title'));

        $this->nodeData->removeProperty('title');

        self::assertFalse($this->nodeData->hasProperty('title'));

        self::assertNotContains('title', $this->nodeData->getPropertyNames());

        self::assertArrayNotHasKey('title', $this->nodeData->getProperties());
    }

    /**
     * @test
     */
    public function propertiesHandlesNullValuesCorrectly()
    {
        $this->nodeData->setProperty('value', null);

        self::assertTrue($this->nodeData->hasProperty('value'));

        self::assertNull($this->nodeData->getProperty('value'));

        self::assertContains('value', $this->nodeData->getPropertyNames());

        self::assertArrayHasKey('value', $this->nodeData->getProperties());

        $this->nodeData->removeProperty('value');

        self::assertFalse($this->nodeData->hasProperty('value'));

        self::assertNotContains('value', $this->nodeData->getPropertyNames());

        self::assertArrayNotHasKey('value', $this->nodeData->getProperties());
    }

    /**
     * @test
     */
    public function removePropertyThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(NodeException::class);
        $this->nodeData->removeProperty('nada');
    }

    /**
     * @test
     */
    public function removePropertyDoesNotTouchAContentObject()
    {
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock(PersistenceManagerInterface::class));

        $className = uniqid('Test', false);
        eval('class ' .$className . ' {
				public $title = "My Title";
			}');
        $contentObject = new $className();
        $this->nodeData->setContentObject($contentObject);

        $this->nodeData->removeProperty('title');

        self::assertTrue($this->nodeData->hasProperty('title'));
        self::assertEquals('My Title', $this->nodeData->getProperty('title'));
    }

    /**
     * @test
     */
    public function propertyFunctionsUseAContentObjectIfOneHasBeenDefined()
    {
        $this->inject($this->nodeData, 'persistenceManager', $this->createMock(PersistenceManagerInterface::class));

        $className = uniqid('Test', false);
        eval('
			class ' .$className . ' {
				public $title = "My Title";
				public $body = "My Body";
			}
		');
        $contentObject = new $className;

        $this->nodeData->setContentObject($contentObject);

        self::assertTrue($this->nodeData->hasProperty('title'));
        self::assertFalse($this->nodeData->hasProperty('iltfh'));

        self::assertEquals('My Body', $this->nodeData->getProperty('body'));
        self::assertEquals('My Title', $this->nodeData->getProperty('title'));

        self::assertEquals(['title' => 'My Title', 'body' => 'My Body'], $this->nodeData->getProperties());

        $actualPropertyNames = $this->nodeData->getPropertyNames();
        sort($actualPropertyNames);
        self::assertEquals(['body', 'title'], $actualPropertyNames);

        $this->nodeData->setProperty('title', 'My Other Title');
        $this->nodeData->setProperty('body', 'My Other Body');

        self::assertEquals('My Other Body', $this->nodeData->getProperty('body'));
        self::assertEquals('My Other Title', $this->nodeData->getProperty('title'));
    }

    /**
     * @test
     */
    public function getPropertyThrowsAnExceptionIfTheSpecifiedPropertyDoesNotExistInTheContentObject()
    {
        $this->expectException(NodeException::class);
        $className = uniqid('Test', false);
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
        /** @var NodeTypeManager|\PHPUnit\Framework\MockObject\MockObject $mockNodeTypeManager */
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects(self::any())->method('hasNodeType')->will(self::returnValue(true));
        $mockNodeTypeManager->expects(self::any())->method('getNodeType')->will(self::returnCallback(
            function ($name) {
                return new NodeType($name, [], []) ;
            }
        ));

        $this->inject($this->nodeData, 'nodeTypeManager', $mockNodeTypeManager);

        self::assertEquals('unstructured', $this->nodeData->getNodeType()->getName());

        $myNodeType = $mockNodeTypeManager->getNodeType('typo3:mycontent');
        $this->nodeData->setNodeType($myNodeType);
        self::assertEquals($myNodeType, $this->nodeData->getNodeType());
    }

    /**
     * @test
     */
    public function getNodeTypeReturnsFallbackNodeTypeForUnknownNodeType()
    {
        $mockFallbackNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $mockNonExistingNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNonExistingNodeType->expects(self::atLeastOnce())->method('getName')->willReturn('definitelyNotAvailableNodeType');

        /** @var NodeTypeManager|\PHPUnit\Framework\MockObject\MockObject $mockNodeTypeManager */
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects(self::atLeastOnce())->method('getNodeType')->with('definitelyNotAvailableNodeType')->will(self::returnValue($mockFallbackNodeType));
        $this->inject($this->nodeData, 'nodeTypeManager', $mockNodeTypeManager);
        $this->inject($this->nodeData, 'nodeType', $mockNonExistingNodeType);

        self::assertSame($mockFallbackNodeType, $this->nodeData->getNodeType());
    }

    /**
     * @test
     */
    public function createNodeCreatesAChildNodeOfTheCurrentNodeInTheContextWorkspace()
    {
        $this->marktestIncomplete('Should be refactored to a contextualized node test.');

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::once())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['countByParentAndNodeType', 'add'])->getMock();
        $nodeDataRepository->expects(self::once())->method('countByParentAndNodeType')->with('/', null, $this->mockWorkspace)->will(self::returnValue(0));
        $nodeDataRepository->expects(self::once())->method('add');
        $nodeDataRepository->expects(self::any())->method('getContext')->will(self::returnValue($context));

        /** @var NodeData|\PHPUnit\Framework\MockObject\MockObject $currentNode */
        $currentNode = $this->getAccessibleMock(NodeData::class, ['getNode'], ['/', $this->mockWorkspace]);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $currentNode->expects(self::once())->method('createProxyForContextIfNeeded')->will($this->returnArgument(0));
        $currentNode->expects(self::once())->method('filterNodeByContext')->will($this->returnArgument(0));

        $newNode = $currentNode->createNode('foo', 'mynodetype');
        self::assertSame($currentNode, $newNode->getParent());
        self::assertEquals(1, $newNode->getIndex());
        self::assertEquals('mynodetype', $newNode->getNodeType()->getName());
    }

    /**
     * @test
     */
    public function createNodeThrowsNodeExceptionIfPathAlreadyExists()
    {
        $this->expectException(NodeException::class);
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects(self::any())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));

        $oldNode = $this->getAccessibleMock(NodeData::class, [], ['/foo', $this->mockWorkspace]);

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['findOneByPath', 'getContext'])->getMock();
        $nodeDataRepository->expects(self::any())->method('findOneByPath')->with('/foo', $this->mockWorkspace)->will(self::returnValue($oldNode));

        /** @var NodeData|\PHPUnit\Framework\MockObject\MockObject $currentNode */
        $currentNode = $this->getAccessibleMock(NodeData::class, ['getNode'], ['/', $this->mockWorkspace]);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->_set('context', $mockContext);

        $currentNode->createNodeData('foo');
    }

    /**
     * @ test
     */
    public function getNodeReturnsNullIfTheSpecifiedNodeDoesNotExist()
    {
        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->setMethods(['findOneByPath', 'getContext'])->getMock();
        $nodeDataRepository->expects(self::once())->method('findOneByPath')->with('/foo/quux', $this->mockWorkspace)->will(self::returnValue(null));

        $currentNode = $this->getAccessibleMock(NodeData::class, ['normalizePath', 'getContext'], ['/foo/baz', $this->mockWorkspace]);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);
        $currentNode->expects(self::once())->method('normalizePath')->with('/foo/quux')->will(self::returnValue('/foo/quux'));

        self::assertNull($currentNode->getNode('/foo/quux'));
    }

    /**
     * @test
     */
    public function getChildNodeDataFindsUnreducedNodeDataChildren()
    {
        $childNodeData = $this->getMockBuilder(NodeData::class)->setConstructorArgs(['/foo/bar', $this->mockWorkspace])->getMock();
        $nodeType = $this->getMockBuilder(NodeType::class)->setConstructorArgs(['mynodetype', [], []])->getMock();
        $childNodeData->setNodeType($nodeType);
        $childNodeDataResults = [
            $childNodeData
        ];

        $nodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $nodeDataRepository->method('findByParentWithoutReduce')->with('/foo', $this->mockWorkspace)->willReturnOnConsecutiveCalls($childNodeDataResults, []);

        $nodeData = $this->getAccessibleMock(NodeData::class, ['dummy'], ['/foo', $this->mockWorkspace]);
        $this->inject($nodeData, 'nodeDataRepository', $nodeDataRepository);

        self::assertSame($childNodeDataResults, $nodeData->_call('getChildNodeData', 'mynodetype'));
        self::assertSame([], $nodeData->_call('getChildNodeData', 'notexistingnodetype'));
    }


    /**
     * @test
     */
    public function removeFlagsTheNodeAsRemoved()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $workspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $nodeDataRepository = $this->getAccessibleMock(NodeDataRepository::class, ['setRemoved', 'update', 'remove'], [], '', false);
        $this->inject($nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock(NodeData::class, ['addOrUpdate'], ['/foo', $workspace]);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects(self::never())->method('remove');

        $currentNode->remove();

        self::assertTrue($currentNode->isRemoved());
    }

    /**
     * @test
     */
    public function removeRemovesTheNodeFromRepositoryIfItsWorkspaceHasNoOtherBaseWorkspace()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $workspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspace->expects(self::once())->method('getBaseWorkspace')->will(self::returnValue(null));

        $nodeDataRepository = $this->getAccessibleMock(NodeDataRepository::class, ['remove', 'update'], [], '', false);
        $this->inject($nodeDataRepository, 'entityClassName', NodeData::class);
        $this->inject($nodeDataRepository, 'persistenceManager', $mockPersistenceManager);

        $currentNode = $this->getAccessibleMock(NodeData::class, null, ['/foo', $workspace]);
        $this->inject($currentNode, 'persistenceManager', $mockPersistenceManager);
        $this->inject($currentNode, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects(self::once())->method('remove');
        $mockPersistenceManager->expects(self::once())->method('isNewObject')->with($currentNode)->willReturn(false);

        $currentNode->remove();
    }

    /**
     * @return array
     */
    public function hasAccessRestrictionsDataProvider()
    {
        return [
            ['accessRoles' => null, 'expectedResult' => false],
            ['accessRoles' => [], 'expectedResult' => false],
            ['accessRoles' => ['Neos.Flow:Everybody'], 'expectedResult' => false],

            ['accessRoles' => ['Some.Other:Role'], 'expectedResult' => true],
            ['accessRoles' => ['Neos.Flow:Everybody', 'Some.Other:Role'], 'expectedResult' => true],
        ];
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
            self::assertTrue($this->nodeData->hasAccessRestrictions());
        } else {
            self::assertFalse($this->nodeData->hasAccessRestrictions());
        }
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfAccessRolesIsNotSet()
    {
        self::assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfSecurityContextCannotBeInitialized()
    {
        /** @var SecurityContext|\PHPUnit\Framework\MockObject\MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects($this->once(0))->method('canBeInitialized')->will(self::returnValue(false));
        $mockSecurityContext->expects(self::never())->method('hasRole');
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(['SomeOtherRole']);
        self::assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsFalseIfAccessRolesIsSetAndSecurityContextHasNoRoles()
    {
        /** @var SecurityContext|\PHPUnit\Framework\MockObject\MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->expects(self::any())->method('isInitialized')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::any())->method('hasRole')->will(self::returnValue(false));
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(['SomeRole']);
        self::assertFalse($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfAccessRolesIsSetAndSecurityContextHasOneOfTheRequiredRoles()
    {
        /** @var SecurityContext|\PHPUnit\Framework\MockObject\MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects(self::atLeast(2))->method('hasRole')->withConsecutive(['SomeRole'], ['SomeOtherRole'])->willReturnOnConsecutiveCalls(false, true);
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(['SomeRole', 'SomeOtherRole']);
        self::assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function isAccessibleReturnsTrueIfRoleIsEveryone()
    {
        /** @var SecurityContext|\PHPUnit\Framework\MockObject\MockObject $mockSecurityContext */
        $mockSecurityContext = $this->createMock(SecurityContext::class);
        $mockSecurityContext->method('canBeInitialized')->willReturn(true);
        $mockSecurityContext->expects(self::atLeast(2))->method('hasRole')->withConsecutive(['SomeRole'], ['Everyone'])->willReturnOnConsecutiveCalls(false, true);
        $this->inject($this->nodeData, 'securityContext', $mockSecurityContext);

        $this->nodeData->setAccessRoles(['SomeRole', 'Everyone', 'SomeOtherRole']);
        self::assertTrue($this->nodeData->isAccessible());
    }

    /**
     * @test
     */
    public function createNodeCreatesNodeDataWithExplicitWorkspaceIfGiven()
    {
        /** @var NodeDataRepository|\PHPUnit\Framework\MockObject\MockObject $nodeDataRepository */
        $nodeDataRepository = $this->createMock(NodeDataRepository::class);
        $this->inject($this->nodeData, 'nodeDataRepository', $nodeDataRepository);

        $nodeDataRepository->expects(self::atLeastOnce())->method('setNewIndex');

        $this->nodeData->createNodeData('foo', null, null, $this->mockWorkspace);
    }

    /**
     * @test
     */
    public function similarizeClearsPropertiesBeforeAddingNewOnes()
    {
        /** @var $sourceNode NodeData */
        $sourceNode = $this->getAccessibleMock(NodeData::class, ['addOrUpdate'], ['/foo/bar', $this->mockWorkspace]);
        $this->inject($sourceNode, 'nodeTypeManager', $this->mockNodeTypeManager);
        $sourceNode->_set('nodeDataRepository', $this->createMock(RepositoryInterface::class));

        $this->nodeData->setProperty('someProperty', 'somePropertyValue');
        $this->nodeData->setProperty('someOtherProperty', 'someOtherPropertyValue');

        $sourceNode->setProperty('newProperty', 'newPropertyValue');
        $sourceNode->setProperty('someProperty', 'someOverriddenPropertyValue');
        $this->nodeData->similarize($sourceNode);

        $expectedProperties = [
            'newProperty' => 'newPropertyValue',
            'someProperty' => 'someOverriddenPropertyValue'
        ];
        self::assertEquals($expectedProperties, $this->nodeData->getProperties());
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithDifferentWorkspaceReturnsFalse()
    {
        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        /** @var Workspace|\PHPUnit\Framework\MockObject\MockObject $otherWorkspace */
        $otherWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $otherWorkspace->expects(self::any())->method('getName')->will(self::returnValue('other'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($otherWorkspace, null);
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithDifferentDimensionReturnsFalse()
    {
        $this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['language' => ['en_US']]);

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, ['language' => ['de_DE', 'mul_ZZ']]);
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function matchesWorkspaceAndDimensionsWithMatchingWorkspaceAndDimensionsReturnsTrue()
    {
        $this->nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['language' => ['mul_ZZ']]);

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $result = $this->nodeData->matchesWorkspaceAndDimensions($this->mockWorkspace, ['language' => ['de_DE', 'mul_ZZ']]);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function getDimensionValuesReturnsDimensionsSortedByKey()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['c' => ['c1', 'c2'], 'a' => ['a1']]);
        $dimensionValues = $nodeData->getDimensionValues();

        self::assertSame(['a' => ['a1'], 'c' => ['c1', 'c2']], $dimensionValues);
    }

    /**
     * @test
     */
    public function dimensionsHashIsOrderIndependent()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['c' => ['c1', 'c2'], 'a' => ['a1']]);
        $dimensionsHash = $nodeData->getDimensionsHash();

        self::assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);

        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['a' => ['a1'], 'c' => ['c2', 'c1']]);
        $dimensionsHash = $nodeData->getDimensionsHash();

        self::assertSame('955c716a191a0957f205ea9376600e72', $dimensionsHash);
    }

    /**
     * @test
     */
    public function setDimensionsAddsDimensionValues()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace);

        $dimensionsToSet = [
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'c', 'c2'),
            new NodeDimension($nodeData, 'a', 'a1'),
            new NodeDimension($nodeData, 'b', 'b1')
        ];
        $expectedDimensionValues = [
            'a' => ['a1'],
            'b' => ['b1'],
            'c' => ['c1', 'c2']
        ];

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        self::assertSame($expectedDimensionValues, $setDimensionValues);
    }

    /**
     * @test
     */
    public function setDimensionsAddsNewDimensionValues()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['c' => ['c1', 'c2'], 'a' => ['a1']]);

        $dimensionsToSet = [
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'c', 'c2'),
            new NodeDimension($nodeData, 'a', 'a1'),
            new NodeDimension($nodeData, 'b', 'b1')
        ];
        $expectedDimensionValues = [
            'a' => ['a1'],
            'b' => ['b1'],
            'c' => ['c1', 'c2']
        ];

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        self::assertSame($expectedDimensionValues, $setDimensionValues);
    }

    /**
     * @test
     */
    public function setDimensionsRemovesDimensionValuesNotGiven()
    {
        $nodeData = new NodeData('/foo/bar', $this->mockWorkspace, null, ['c' => ['c1', 'c2'], 'a' => ['a1']]);

        $dimensionsToSet = [
            new NodeDimension($nodeData, 'c', 'c1'),
            new NodeDimension($nodeData, 'b', 'b1'),
            new NodeDimension($nodeData, 'f', 'f1')
        ];
        $expectedDimensionValues = [
            'b' => ['b1'],
            'c' => ['c1'],
            'f' => ['f1']
        ];

        $nodeData->setDimensions($dimensionsToSet);
        $setDimensionValues = $nodeData->getDimensionValues();

        self::assertSame($expectedDimensionValues, $setDimensionValues);
    }
}
