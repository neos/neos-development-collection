<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service\ImportExport;

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
use TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService;

/**
 * Tests for the NodeExportService class
 */
class NodeExportServiceTest extends UnitTestCase
{
    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    public function setUp()
    {
        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects($this->any())->method('withoutAuthorizationChecks')->will($this->returnCallback(function ($callback) {
            return $callback->__invoke();
        }));
    }

    /**
     * @test
     */
    public function exportEmptyListOfNodesCreatesEmptyXml()
    {
        /** @var NodeExportService|\PHPUnit_Framework_MockObject_MockObject $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);

        $nodeDataList = array();
        $nodeExportService->expects($this->any())->method('findNodeDataListToExport')->will($this->returnValue($nodeDataList));

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
        /** @var NodeExportService|\PHPUnit_Framework_MockObject_MockObject $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);
        $nodeTypeManager = $this->getMock('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
        $this->inject($nodeExportService, 'nodeTypeManager', $nodeTypeManager);
        $nodeTypeManager->expects($this->once())->method('hasNodeType')->willReturn(false);

        $nodeData = $this->buildNodeDataArray(
            '/',
            'e645d5fc-b1d7-11e4-a9a3-14109fd7a2dd',
            array(
                'version' => 2
            )
        );

        $nodeDataList = array($nodeData);
        $nodeExportService->expects($this->any())->method('findNodeDataListToExport')->will($this->returnValue($nodeDataList));

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
        /** @var NodeExportService|\PHPUnit_Framework_MockObject_MockObject $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));
        $this->inject($nodeExportService, 'securityContext', $this->mockSecurityContext);
        $nodeTypeManager = $this->getMock('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
        $this->inject($nodeExportService, 'nodeTypeManager', $nodeTypeManager);
        $nodeTypeManager->expects($this->once())->method('hasNodeType')->willReturn(false);

        $nodeData1 = $this->buildNodeDataArray(
            '/sites/foo',
            '25eab5ec-b1dd-11e4-8823-14109fd7a2dd',
            array(
                'nodeType' => 'TYPO3.TYPO3CR.Testing:Page',
                'version' => 3,
                'properties' => array('title' => 'Foo')
            )
        );

        $nodeData2 = $this->buildNodeDataArray(
            '/sites/foo/home/about',
            '27dde996-b1dd-11e4-8909-14109fd7a2dd',
            array(
                'nodeType' => 'TYPO3.TYPO3CR.Testing:Page',
                'version' => 2,
                'properties' => array('title' => 'About us')
            )
        );

        $nodeDataList = array($nodeData1, $nodeData2);
        $nodeExportService->expects($this->any())->method('findNodeDataListToExport')->will($this->returnValue($nodeDataList));

        $mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');
        $mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnCallback(function ($source) {
            return $source;
        }));
        $this->inject($nodeExportService, 'propertyMapper', $mockPropertyMapper);

        $xmlWriter = $nodeExportService->export('/sites/foo');
        $output = $xmlWriter->outputMemory();

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?>
			<nodes formatVersion="2.0">
			  <node identifier="25eab5ec-b1dd-11e4-8823-14109fd7a2dd" nodeName="foo">
				<variant hidden="" hiddenInIndex="" nodeType="TYPO3.TYPO3CR.Testing:Page" removed="" sortingIndex="0" version="3" workspace="live">
				  <dimensions/>
				  <accessRoles __type="array"/>
				  <properties>
					<title __type="string">Foo</title>
				  </properties>
				</variant>
			  </node>
			</nodes>
		', $output);
        $this->assertContains('<!--Skipped node', $output);
    }

    /**
     * @param string $path
     * @param string $identifier
     * @param array $additionalProperties
     * @return array
     */
    protected function buildNodeDataArray($path, $identifier, $additionalProperties = array())
    {
        $parentPath = substr($path, 0, strrpos($path, '/'));
        $nodeData = array(
            'path' => $path,
            'identifier' => $identifier,
            'sortingIndex' => 0,
            'properties' => array(),
            'nodeType' => 'unstructured',
            'removed' => false,
            'hidden' => false,
            'hiddenBeforeDateTime' => null,
            'hiddenAfterDateTime' => null,
            'hiddenInIndex' => false,
            'accessRoles' => array(),
            'version' => 1,
            'pathHash' => md5($path),
            'dimensionValues' => array(),
            'dimensionsHash' => 'd751713988987e9331980363e24189ce',
            'parentPath' => $parentPath,
            'parentPathHash' => md5($parentPath),
            'workspace' => 'live'
        );
        $nodeData = array_merge($nodeData, $additionalProperties);
        return $nodeData;
    }
}
