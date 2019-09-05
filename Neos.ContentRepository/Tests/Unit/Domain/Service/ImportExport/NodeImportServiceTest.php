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

use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService;

class NodeImportServiceTest extends UnitTestCase
{
    /**
     * @var PropertyMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    public function setUp(): void
    {
        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects(self::any())->method('withoutAuthorizationChecks')->will(self::returnCallback(function ($callback) {
            return $callback->__invoke();
        }));
    }

    /**
     * @test
     */
    public function importSingleNode()
    {
        $xmlReader = new \XMLReader();
        $result = $xmlReader->open(__DIR__ . '/Fixtures/SingleNode.xml');

        self::assertTrue($result);

        /** @var NodeImportService $nodeImportService */
        $nodeImportService = $this->getMockBuilder(NodeImportService::class)->setMethods(['persistNodeData'])->getMock();
        $this->inject($nodeImportService, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($nodeImportService, 'securityContext', $this->mockSecurityContext);

        $expectedNodeData = [
            'identifier' => '995c9174-ddd6-4d5c-cfc0-1ffc82184677',
            'nodeType' => 'Acme.Com:Page',
            'workspace' => 'live',
            'sortingIndex' => '100',
            'version' => '14',
            'removed' => false,
            'hidden' => false,
            'hiddenInIndex' => false,
            'path' => '/neosdemoio',
            'pathHash' => '9204ab1d1079b1d950fffecf874955c3',
            'parentPath' => '/',
            'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
            'properties' => [
                'title' => 'Home',
                'layout' => 'landingPage',
                'uriPathSegment' => 'home',
                'imageTitleText' => 'Photo by www.daniel-bischoff.photo',
            ],
            'accessRoles' => [],
            'hiddenBeforeDateTime' => new \DateTime('2015-10-01T03:45:04+02:00'),
            'hiddenAfterDateTime' => new \DateTime('2015-10-22T07:50:04+02:00'),
            'dimensionValues' => [
                'language' => [
                    'en_US',
                    'en_UK'
                ]
            ]
        ];
        $nodeImportService->expects(self::once())->method('persistNodeData')->will(self::returnCallback(function ($nodeData) use (&$actualNodeData) {
            unset($nodeData['Persistence_Object_Identifier']);
            $actualNodeData = $nodeData;
            return true;
        }));
        $this->mockPropertyMapper->expects(self::any())->method('convert')->will(self::returnCallback(function ($source, $targetType) {
            if ($targetType === 'DateTime') {
                return new \DateTime($source);
            }
            throw new \Exception('Target type ' . $targetType . ' not supported in property mapper mock');
        }));
        $this->mockPropertyMapper->expects(self::any())->method('getMessages')->willReturn(new \Neos\Error\Messages\Result());

        $nodeImportService->import($xmlReader, '/');

        self::assertInstanceOf('DateTimeInterface', $actualNodeData['creationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeData['lastModificationDateTime']);
        unset($actualNodeData['creationDateTime']);
        unset($actualNodeData['lastModificationDateTime']);

        self::assertEquals($expectedNodeData, $actualNodeData);
    }

    /**
     * @test
     */
    public function importSingleNodeWithoutIdentifier()
    {
        $xmlReader = new \XMLReader();
        $result = $xmlReader->open(__DIR__ . '/Fixtures/SingleNodeWithoutIdentifier.xml');

        self::assertTrue($result);

        /** @var NodeImportService $nodeImportService */
        $nodeImportService = $this->getMockBuilder(NodeImportService::class)->setMethods(['persistNodeData'])->getMock();
        $this->inject($nodeImportService, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($nodeImportService, 'securityContext', $this->mockSecurityContext);

        $expectedNodeData = [
            'nodeType' => 'Acme.Com:Page',
            'workspace' => 'live',
            'sortingIndex' => '100',
            'version' => '14',
            'removed' => false,
            'hidden' => false,
            'hiddenInIndex' => false,
            'path' => '/neosdemoio',
            'pathHash' => '9204ab1d1079b1d950fffecf874955c3',
            'parentPath' => '/',
            'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
            'properties' => [
            ],
            'accessRoles' => [],
            'dimensionValues' => [
            ]
        ];
        $actualIdentifier = null;
        $nodeImportService->expects(self::once())->method('persistNodeData')->will(self::returnCallback(function ($nodeData) use (&$actualNodeData, &$actualIdentifier) {
            unset($nodeData['Persistence_Object_Identifier']);
            $actualIdentifier = $nodeData['identifier'];
            unset($nodeData['identifier']);
            $actualNodeData = $nodeData;
            return true;
        }));

        $nodeImportService->import($xmlReader, '/');

        self::assertInstanceOf('DateTimeInterface', $actualNodeData['creationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeData['lastModificationDateTime']);
        unset($actualNodeData['creationDateTime']);
        unset($actualNodeData['lastModificationDateTime']);
        self::assertTrue(strlen($actualIdentifier) > 0, 'The identifier was not autogenerated properly.');

        self::assertEquals($expectedNodeData, $actualNodeData);
    }

    /**
     * @test
     */
    public function importWithEmptyPropertyImportsAllProperties()
    {
        $xmlReader = new \XMLReader();
        $result = $xmlReader->open(__DIR__ . '/Fixtures/NodesWithEmptyProperty.xml', null, LIBXML_PARSEHUGE);

        self::assertTrue($result);

        /** @var NodeImportService $nodeImportService */
        $nodeImportService = $this->getMockBuilder(NodeImportService::class)->setMethods(['persistNodeData'])->getMock();
        $this->inject($nodeImportService, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($nodeImportService, 'securityContext', $this->mockSecurityContext);

        $expectedNodeDatas = [
            [
                'identifier' => '995c9174-ddd6-4d5c-cfc0-1ffc82184677',
                'nodeType' => 'Acme.Com:Page',
                'workspace' => 'live',
                'sortingIndex' => '100',
                'version' => '14',
                'removed' => false,
                'hidden' => false,
                'hiddenInIndex' => false,
                'path' => '/neosdemoio',
                'pathHash' => '9204ab1d1079b1d950fffecf874955c3',
                'parentPath' => '/',
                'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
                'properties' => [
                    'title' => 'Home',
                    'layout' => 'landingPage',
                    'uriPathSegment' => 'home',
                    'image' =>
                        [
                            'targetType' => \Neos\Media\Domain\Model\ImageVariant::class,
                            'source' =>
                                [
                                    'originalImage' =>
                                        [
                                            'title' => '',
                                            'resource' =>
                                                [
                                                    'filename' => 'enjoy-the-view-by-daniel-bischoff.jpg',
                                                    'hash' => 'a0fcd1a8d6529424beaa69e070320ee9dc387723',
                                                    '__identity' => '53488dec-7960-466d-2287-3fb627c3f587',
                                                ],
                                            '__identity' => '4d01043b-d738-cbd3-199a-63cba0f66ef9',
                                        ],
                                    'processingInstructions' =>
                                        [
                                            0 =>
                                                [
                                                    'command' => 'crop',
                                                    'options' =>
                                                        [
                                                            'start' =>
                                                                [
                                                                    'x' => 0,
                                                                    'y' => 100,
                                                                ],
                                                            'size' =>
                                                                [
                                                                    'width' => 3000,
                                                                    'height' => 1500,
                                                                ],
                                                        ],
                                                ],
                                            1 =>
                                                [
                                                    'command' => 'resize',
                                                    'options' =>
                                                        [
                                                            'size' =>
                                                                [
                                                                    'width' => 3000,
                                                                    'height' => 1500,
                                                                ],
                                                        ],
                                                ],
                                        ],
                                ],
                        ],
                    'imageTitleText' => 'Photo by www.daniel-bischoff.photo',
                    'relatedDocuments' => []
                ],
                'accessRoles' => [],
                'dimensionValues' => [
                    'language' => ['en_US']
                ],
            ],
            [
                'identifier' => 'e45e3b2c-3f14-2c14-6230-687fa4696504',
                'nodeType' => 'Acme.Com:AssetList',
                'workspace' => 'live',
                'sortingIndex' => '300',
                'version' => '3',
                'removed' => false,
                'hidden' => false,
                'hiddenInIndex' => false,
                'path' => '/node53a18fb53bdf2',
                'pathHash' => 'adb885c51ef09d0d4beec84adff97355',
                'parentPath' => '/',
                'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
                'properties' => [
                    'assets' => [
                        0 => [
                            'targetType' => \Neos\Media\Domain\Model\Image::class,
                            'source' =>
                                [
                                    'title' => '',
                                    'resource' =>
                                        [
                                            'filename' => 'alice-1.jpg',
                                            'hash' => '30d0d71c6e7e4dd53636a8b9a5d5c8fd9b73f10f',
                                            '__identity' => '3640b4ba-a68b-7e2f-4199-d4e3a2b684c3',
                                        ],
                                ],
                        ],
                        1 => [
                            'targetType' => \Neos\Media\Domain\Model\Asset::class,
                            'source' =>
                                [
                                    'title' => '',
                                    'resource' =>
                                        [
                                            'filename' => 'Neos-logo_sRGB_color.pdf',
                                            'hash' => 'bed9a3e45070e97b921877e2bd9c35ba368beca0',
                                            '__identity' => '8a4496e4-fa0d-8550-0995-01fd869728bf',
                                        ],
                                ],
                        ]
                    ],
                    'relatedDocuments' => []
                ],
                'accessRoles' => [],
                'dimensionValues' => [
                    'language' => ['en_US']
                ]
            ]
        ];
        $nodeImportService->expects(self::atLeastOnce())->method('persistNodeData')->will(self::returnCallback(function ($nodeData) use (&$actualNodeDatas) {
            unset($nodeData['Persistence_Object_Identifier']);
            $actualNodeDatas[] = $nodeData;
            return true;
        }));
        $this->mockPropertyMapper->expects(self::any())->method('convert')->will(self::returnCallback(function ($source, $targetType) {
            return [
                'targetType' => $targetType,
                'source' => $source
            ];
        }));
        $this->mockPropertyMapper->expects(self::any())->method('getMessages')->willReturn(new \Neos\Error\Messages\Result());

        $nodeImportService->import($xmlReader, '/');

        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[0]['creationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[0]['lastModificationDateTime']);
        unset($actualNodeDatas[0]['creationDateTime']);
        unset($actualNodeDatas[0]['lastModificationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[1]['creationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[1]['lastModificationDateTime']);
        unset($actualNodeDatas[1]['creationDateTime']);
        unset($actualNodeDatas[1]['lastModificationDateTime']);

        self::assertEquals($expectedNodeDatas, $actualNodeDatas);
    }

    /**
     * @test
     */
    public function importWithArrayPropertiesImportsCorrectly()
    {
        $xmlReader = new \XMLReader();
        $result = $xmlReader->open(__DIR__ . '/Fixtures/NodesWithArrayProperties.xml', null, LIBXML_PARSEHUGE);

        self::assertTrue($result);

        /** @var NodeImportService $nodeImportService */
        $nodeImportService = $this->getMockBuilder(NodeImportService::class)->setMethods(['persistNodeData'])->getMock();
        $this->inject($nodeImportService, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($nodeImportService, 'securityContext', $this->mockSecurityContext);

        $expectedNodeDatas = [
            [
                'identifier' => 'e45e3b2c-3f14-2c14-6230-687fa4696504',
                'nodeType' => 'Acme.Com:Page',
                'workspace' => 'live',
                'sortingIndex' => '300',
                'version' => '3',
                'removed' => false,
                'hidden' => false,
                'hiddenInIndex' => false,
                'path' => '/node53a18fb53bdf2',
                'pathHash' => 'adb885c51ef09d0d4beec84adff97355',
                'parentPath' => '/',
                'parentPathHash' => '6666cd76f96956469e7be39d750cc7d9',
                'properties' => [
                    'foos' => [
                        0 => 'content of entry0',
                        1 => 'content of entry1'
                    ],
                    'bar' => 'a bar walks into a foo',
                    'empty' => null,
                    'bars' => []
                ],
                'accessRoles' => [],
                'dimensionValues' => [
                    'language' => ['en_US']
                ]
            ]
        ];
        $nodeImportService->expects(self::atLeastOnce())->method('persistNodeData')->will(self::returnCallback(function ($nodeData) use (&$actualNodeDatas) {
            unset($nodeData['Persistence_Object_Identifier']);
            $actualNodeDatas[] = $nodeData;
            return true;
        }));
        $this->mockPropertyMapper->expects(self::any())->method('convert')->will(self::returnCallback(function ($source, $targetType) {
            return [
                'targetType' => $targetType,
                'source' => $source
            ];
        }));

        $nodeImportService->import($xmlReader, '/');

        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[0]['creationDateTime']);
        self::assertInstanceOf('DateTimeInterface', $actualNodeDatas[0]['lastModificationDateTime']);
        unset($actualNodeDatas[0]['creationDateTime']);
        unset($actualNodeDatas[0]['lastModificationDateTime']);

        self::assertEquals($expectedNodeDatas, $actualNodeDatas);
    }

    /**
     * @test
     */
    public function importWithLinebreakInDateTimeImportsCorrectly()
    {
        $xmlReader = new \XMLReader();
        $result = $xmlReader->open(__DIR__ . '/Fixtures/SingleNodeWithLinebreaks.xml', null, LIBXML_PARSEHUGE);

        self::assertTrue($result);

        /** @var \Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService $nodeImportService */
        $nodeImportService = $this->getMockBuilder(\Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService::class)->setMethods(['persistNodeData'])->getMock();
        $this->inject($nodeImportService, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($nodeImportService, 'securityContext', $this->mockSecurityContext);

        $expectedNodeDatas = [
            [
                'creationDateTime' => [
                    'source' => '2015-12-21T21:56:53+00:00'
                ]
            ]
        ];
        $nodeImportService->expects(self::atLeastOnce())->method('persistNodeData')->will(self::returnCallback(function ($nodeData) use (&$actualNodeDatas) {
            unset($nodeData['Persistence_Object_Identifier']);
            $actualNodeDatas[] = $nodeData;
            return true;
        }));
        $this->mockPropertyMapper->expects(self::any())->method('convert')->will(self::returnCallback(function ($source, $targetType) {
            return [
                'targetType' => $targetType,
                'source' => $source
            ];
        }));
        $this->mockPropertyMapper->expects(self::any())->method('getMessages')->willReturn(new \Neos\Error\Messages\Result());

        $nodeImportService->import($xmlReader, '/');

        self::assertCount(1, $actualNodeDatas);

        self::assertEquals($expectedNodeDatas[0]['creationDateTime']['source'], $actualNodeDatas[0]['creationDateTime']['source']);
    }
}
