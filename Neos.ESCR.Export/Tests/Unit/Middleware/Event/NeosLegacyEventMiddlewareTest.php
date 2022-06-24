<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Tests\Unit\Middleware\Event;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ESCR\Export\Middleware\Event\NeosLegacyEventMiddleware;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NeosLegacyEventMiddlewareTest extends UnitTestCase
{
    private NeosLegacyEventMiddleware $middleware;
    private Connection|MockObject $mockConnection;
    private InterDimensionalVariationGraph|MockObject $mockInterDimensionalVariationGraph;
    private ContentDimensionSourceInterface|MockObject $mockContentDimensionSource;
    private NodeTypeManager|MockObject $mockNodeTypeManager;
    private PropertyMapper|MockObject $mockPropertyMapper;
    private EventNormalizer|MockObject $mockEventNormalizer;

    public function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockInterDimensionalVariationGraph = $this->getMockBuilder(InterDimensionalVariationGraph::class)->disableOriginalConstructor()->getMock();
        $this->mockContentDimensionSource = $this->getMockBuilder(ContentDimensionSourceInterface::class)->getMock();
        $mockContentDimensionZookeeper = new ContentDimensionZookeeper($this->mockContentDimensionSource);
        $this->mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $this->mockEventNormalizer = $this->getMockBuilder(EventNormalizer::class)->disableOriginalConstructor()->getMock();

        $this->middleware = new NeosLegacyEventMiddleware($this->mockConnection, $this->mockInterDimensionalVariationGraph, $mockContentDimensionZookeeper, $this->mockNodeTypeManager, $this->mockPropertyMapper, $this->mockEventNormalizer, $this->mockPropertyConverter);
    }

    public function test_foo(): void
    {
        $d = new NeosLegacyEventMiddleware()
        self::assertSame('foo', 'bar');
    }
}
