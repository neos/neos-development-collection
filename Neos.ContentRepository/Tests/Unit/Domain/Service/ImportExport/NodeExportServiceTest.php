<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service\ImportExport;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Tests for the NodeExportService class
 */
class NodeExportServiceTest extends UnitTestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    public function setUp(): void
    {
        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects(self::any())->method('withoutAuthorizationChecks')->will(self::returnCallback(function ($callback) {
            return $callback->__invoke();
        }));
    }

    /**
     * @test
     */
    public function exportEmptyListOfNodesCreatesEmptyXml()
    {
        /** @var NodeExportService|\PHPUnit\Framework\MockObject\MockObject $nodeExportService */
        $nodeExportService = $this->getMockBuilder(NodeExportService::class)->setMethods(['findNodeDataListToExport'])->getMock();
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);

        $nodeDataList = [];
        $nodeExportService->expects(self::any())->method('findNodeDataListToExport')->will(self::returnValue($nodeDataList));

        $xmlWriter = $nodeExportService->export();
        $output = $xmlWriter->outputMemory();

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="UTF-8"?>
			<nodes formatVersion="2.0"/>
		', $output);
    }

    /**
     * @test
     */
    public function exportRootNodeCreatesSingleNode()
    {
        /** @var NodeExportService|\PHPUnit\Framework\MockObject\MockObject $nodeExportService */
        $nodeExportService = $this->getMockBuilder(NodeExportService::class)->setMethods(['findNodeDataListToExport'])->getMock();
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);
        $nodeTypeManager = $this->createMock(NodeTypeManager::class);
        $this->inject($nodeExportService, 'nodeTypeManager', $nodeTypeManager);
        $nodeTypeManager->expects(self::once())->method('hasNodeType')->willReturn(false);

        $nodeData = $this->buildNodeDataArray(
            '/',
            'e645d5fc-b1d7-11e4-a9a3-14109fd7a2dd',
            [
                'version' => 2
            ]
        );

        $nodeDataList = [$nodeData];
        $nodeExportService->expects(self::any())->method('findNodeDataListToExport')->will(self::returnValue($nodeDataList));

        $xmlWriter = $nodeExportService->export();
        $output = $xmlWriter->outputMemory();

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
			<nodes formatVersion="2.0">
			  <node identifier="e645d5fc-b1d7-11e4-a9a3-14109fd7a2dd" nodeName="">
				<variant hidden="" hiddenInIndex="" nodeType="unstructured" removed="" sortingIndex="0" version="2" workspace="live">
				  <dimensions/>
				  <accessRoles __type="array"/>
				  <properties/>
				</variant>
			  </node>
			</nodes>
		', $output);
    }

    /**
     * @test
     */
    public function exportNodeWithoutParentSkipsBrokenNode()
    {
        /** @var NodeExportService|\PHPUnit\Framework\MockObject\MockObject $nodeExportService */
        $nodeExportService = $this->getMockBuilder(NodeExportService::class)->setMethods(['findNodeDataListToExport'])->getMock();
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);
        $nodeTypeManager = $this->createMock(NodeTypeManager::class);
        $this->inject($nodeExportService, 'nodeTypeManager', $nodeTypeManager);
        $nodeTypeManager->expects(self::once())->method('hasNodeType')->willReturn(false);

        $nodeData1 = $this->buildNodeDataArray(
            '/sites/foo',
            '25eab5ec-b1dd-11e4-8823-14109fd7a2dd',
            [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'version' => 3,
                'properties' => ['title' => 'Foo']
            ]
        );

        $nodeData2 = $this->buildNodeDataArray(
            '/sites/foo/home/about',
            '27dde996-b1dd-11e4-8909-14109fd7a2dd',
            [
                'nodeType' => 'Neos.ContentRepository.Testing:Page',
                'version' => 2,
                'properties' => ['title' => 'About us']
            ]
        );

        $nodeDataList = [$nodeData1, $nodeData2];
        $nodeExportService->expects(self::any())->method('findNodeDataListToExport')->will(self::returnValue($nodeDataList));

        $mockPropertyMapper = $this->createMock(\Neos\Flow\Property\PropertyMapper::class);
        $mockPropertyMapper->expects(self::any())->method('convert')->will(self::returnCallback(function ($source) {
            return $source;
        }));
        $this->inject($nodeExportService, 'propertyMapper', $mockPropertyMapper);

        $xmlWriter = $nodeExportService->export('/sites/foo');
        $output = $xmlWriter->outputMemory();

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
			<nodes formatVersion="2.0">
			  <node identifier="25eab5ec-b1dd-11e4-8823-14109fd7a2dd" nodeName="foo">
				<variant hidden="" hiddenInIndex="" nodeType="Neos.ContentRepository.Testing:Page" removed="" sortingIndex="0" version="3" workspace="live">
				  <dimensions/>
				  <accessRoles __type="array"/>
				  <properties>
					<title __type="string">Foo</title>
				  </properties>
				</variant>
			  </node>
			</nodes>
		', $output);
        self::assertStringContainsString('<!--Skipped node', $output);
    }

    /**
     * @param string $path
     * @param string $identifier
     * @param array $additionalProperties
     * @return array
     */
    protected function buildNodeDataArray($path, $identifier, $additionalProperties = [])
    {
        $parentPath = substr($path, 0, strrpos($path, '/'));
        $nodeData = [
            'path' => $path,
            'identifier' => $identifier,
            'sortingIndex' => 0,
            'properties' => [],
            'nodeType' => 'unstructured',
            'removed' => false,
            'hidden' => false,
            'hiddenBeforeDateTime' => null,
            'hiddenAfterDateTime' => null,
            'hiddenInIndex' => false,
            'accessRoles' => [],
            'version' => 1,
            'pathHash' => md5($path),
            'dimensionValues' => [],
            'dimensionsHash' => 'd751713988987e9331980363e24189ce',
            'parentPath' => $parentPath,
            'parentPathHash' => md5($parentPath),
            'workspace' => 'live'
        ];
        $nodeData = array_merge($nodeData, $additionalProperties);
        return $nodeData;
    }
}
