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

class NodeImportServiceTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function importSingleNode() {
		$xmlReader = new \XMLReader();
		$result = $xmlReader->open(__DIR__ . '/Fixtures/SingleNode.xml');

		$this->assertTrue($result);

		$mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');

		/** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService $nodeImportService */
		$nodeImportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService', array('persistNodeData'));
		$this->inject($nodeImportService, 'propertyMapper', $mockPropertyMapper);

		$expectedNodeData = array(
			'identifier' => '995c9174-ddd6-4d5c-cfc0-1ffc82184677',
			'nodeType' => 'TYPO3.Neos.NodeTypes:Page',
			'workspace' => 'live',
			'sortingIndex' => 100,
			'version' => 14,
			'removed' => FALSE,
			'hidden' => FALSE,
			'hiddenInIndex' => FALSE,
			'path' => 'neosdemotypo3org',
			'pathHash' => 'c3d599119f05d37fae5fc59b8e04c2a4',
			'parentPath' => '/',
			'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
			'properties' => array(
				'title' => 'Home',
				'layout' => 'landingPage',
				'uriPathSegment' => 'home',
				'imageTitleText' => 'Photo by www.daniel-bischoff.photo',
			),
			'accessRoles' => array(),
			'dimensionValues' => array(
				'language' => array(
					'en_US',
					'en_UK'
				)
			)
		);
		$nodeImportService->expects($this->once())->method('persistNodeData')->will($this->returnCallback(function ($nodeData) use (&$actualNodeData) {
			unset($nodeData['Persistence_Object_Identifier']);
			$actualNodeData = $nodeData;
			return TRUE;
		}));

		$nodeImportService->import($xmlReader, '/');

		$this->assertEquals($expectedNodeData, $actualNodeData);
	}

	/**
	 * @test
	 */
	public function importWithEmptyPropertyImportsAllProperties() {
		$xmlReader = new \XMLReader();
		$result = $xmlReader->open(__DIR__ . '/Fixtures/NodesWithEmptyProperty.xml', NULL, LIBXML_PARSEHUGE);

		$this->assertTrue($result);

		$mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');

		/** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService $nodeImportService */
		$nodeImportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService', array('persistNodeData'));
		$this->inject($nodeImportService, 'propertyMapper', $mockPropertyMapper);

		$expectedNodeDatas = array(
			array(
				'identifier' => '995c9174-ddd6-4d5c-cfc0-1ffc82184677',
				'nodeType' => 'TYPO3.Neos.NodeTypes:Page',
				'workspace' => 'live',
				'sortingIndex' => '100',
				'version' => '14',
				'removed' => FALSE,
				'hidden' => FALSE,
				'hiddenInIndex' => FALSE,
				'path' => 'neosdemotypo3org',
				'pathHash' => 'c3d599119f05d37fae5fc59b8e04c2a4',
				'parentPath' => '/',
				'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
				'properties' => array(
					'title' => 'Home',
					'layout' => 'landingPage',
					'uriPathSegment' => 'home',
					'image' =>
						array(
							'targetType' => 'TYPO3\Media\Domain\Model\ImageVariant',
							'source' =>
								array(
									'originalImage' =>
										array(
											'title' => '',
											'resource' =>
												array(
													'filename' => 'enjoy-the-view-by-daniel-bischoff.jpg',
													'hash' => 'a0fcd1a8d6529424beaa69e070320ee9dc387723',
													'__identity' => '53488dec-7960-466d-2287-3fb627c3f587',
												),
											'__identity' => '4d01043b-d738-cbd3-199a-63cba0f66ef9',
										),
									'processingInstructions' =>
										array(
											0 =>
												array(
													'command' => 'crop',
													'options' =>
														array(
															'start' =>
																array(
																	'x' => 0,
																	'y' => 100,
																),
															'size' =>
																array(
																	'width' => 3000,
																	'height' => 1500,
																),
														),
												),
											1 =>
												array(
													'command' => 'resize',
													'options' =>
														array(
															'size' =>
																array(
																	'width' => 3000,
																	'height' => 1500,
																),
														),
												),
										),
								),
							'Persistence_Object_Identifier' => NULL
						),
					'imageTitleText' => 'Photo by www.daniel-bischoff.photo',
					'relatedDocuments' => array()
				),
				'accessRoles' => array(),
				'dimensionValues' => array(
					'language' => array('en_US')
				)
			),
			array(
				'identifier' => 'e45e3b2c-3f14-2c14-6230-687fa4696504',
				'nodeType' => 'TYPO3.Neos.NodeTypes:AssetList',
				'workspace' => 'live',
				'sortingIndex' => '300',
				'version' => '3',
				'removed' => FALSE,
				'hidden' => FALSE,
				'hiddenInIndex' => FALSE,
				'path' => 'node53a18fb53bdf2',
				'pathHash' => '3f1d4fea7c0b21d21098960149de9c80',
				'parentPath' => '/',
				'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
				'properties' => array(
					'assets' => array(
						0 => array(
							'targetType' => 'TYPO3\Media\Domain\Model\Image',
							'source' =>
								array(
									'title' => '',
									'resource' =>
										array(
											'filename' => 'alice-1.jpg',
											'hash' => '30d0d71c6e7e4dd53636a8b9a5d5c8fd9b73f10f',
											'__identity' => '3640b4ba-a68b-7e2f-4199-d4e3a2b684c3',
										),
								),
							'Persistence_Object_Identifier' => '08aaeb84-dccc-6af4-0086-93499de4e1bc'
						),
						1 => array(
							'targetType' => 'TYPO3\\Media\\Domain\\Model\\Asset',
							'source' =>
								array(
									'title' => '',
									'resource' =>
										array(
											'filename' => 'TYPO3_Neos-logo_sRGB_color.pdf',
											'hash' => 'bed9a3e45070e97b921877e2bd9c35ba368beca0',
											'__identity' => '8a4496e4-fa0d-8550-0995-01fd869728bf',
										),
								),
							'Persistence_Object_Identifier' => '89cd85cc-270e-0902-7113-d14ac7539c75'
						)
					),
					'relatedDocuments' => array()
				),
				'accessRoles' => array(),
				'dimensionValues' => array(
					'language' => array('en_US')
				)
			)
		);
		$nodeImportService->expects($this->atLeastOnce())->method('persistNodeData')->will($this->returnCallback(function ($nodeData) use (&$actualNodeDatas) {
			unset($nodeData['Persistence_Object_Identifier']);
			$actualNodeDatas[] = $nodeData;
			return TRUE;
		}));
		$mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnCallback(function ($source, $targetType) {
			return array(
				'targetType' => $targetType,
				'source' => $source
			);
		}));

		$nodeImportService->import($xmlReader, '/');

		$this->assertEquals($expectedNodeDatas, $actualNodeDatas);
	}

	/**
	 * @test
	 */
	public function importWithArrayPropertiesImportsCorrectly() {
		$xmlReader = new \XMLReader();
		$result = $xmlReader->open(__DIR__ . '/Fixtures/NodesWithArrayProperties.xml', NULL, LIBXML_PARSEHUGE);

		$this->assertTrue($result);

		$mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');

		/** @var \TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService $nodeImportService */
		$nodeImportService = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService', array('persistNodeData'));
		$this->inject($nodeImportService, 'propertyMapper', $mockPropertyMapper);

		$expectedNodeDatas = array(
			array(
				'identifier' => 'e45e3b2c-3f14-2c14-6230-687fa4696504',
				'nodeType' => 'TYPO3.Neos.NodeTypes:Page',
				'workspace' => 'live',
				'sortingIndex' => '300',
				'version' => '3',
				'removed' => FALSE,
				'hidden' => FALSE,
				'hiddenInIndex' => FALSE,
				'path' => 'node53a18fb53bdf2',
				'pathHash' => '3f1d4fea7c0b21d21098960149de9c80',
				'parentPath' => '/',
				'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
				'properties' => array(
					'foos' => array(
						0 => 'content of entry0',
						1 => 'content of entry1'
					),
					'bar' => 'a bar walks into a foo',
					'empty' => NULL,
					'bars' => array()
				),
				'accessRoles' => array(),
				'dimensionValues' => array(
					'language' => array('en_US')
				)
			)
		);
		$nodeImportService->expects($this->atLeastOnce())->method('persistNodeData')->will($this->returnCallback(function ($nodeData) use (&$actualNodeDatas) {
			unset($nodeData['Persistence_Object_Identifier']);
			$actualNodeDatas[] = $nodeData;
			return TRUE;
		}));
		$mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnCallback(function ($source, $targetType) {
			return array(
				'targetType' => $targetType,
				'source' => $source
			);
		}));

		$nodeImportService->import($xmlReader, '/');

		$this->assertEquals($expectedNodeDatas, $actualNodeDatas);
	}
}
