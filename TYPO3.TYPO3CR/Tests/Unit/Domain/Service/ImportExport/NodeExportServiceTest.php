<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service\ImportExport;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;

class NodeExportServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function exportEmptyListOfNodesCreatesEmptyXml()
    {
        /** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));

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
        /** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));

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
        /** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService $nodeExportService */
        $nodeExportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeExportService', array('findNodeDataListToExport'));

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
