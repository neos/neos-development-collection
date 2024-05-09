<?php
namespace Neos\Neos\Tests\Functional\Fusion;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\DefaultNodeLabelGeneratorFactory;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\TestSuite\Unit\NodeSubjectProvider;
use Neos\Fusion\Tests\Functional\FusionObjects\AbstractFusionObjectTest;
use Neos\Fusion\Tests\Functional\FusionObjects\TestingViewForFusionRuntime;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the Fusion NodeLabel helper
 */
class NodeHelperTest extends AbstractFusionObjectTest
{
    /**
     * @var Node|MockObject
     */
    protected $textNode;

    /**
     * @test
     */
    public function defaultNodeLabel()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/defaultLabel');

        $view->assign('node', $this->textNode);

        self::assertEquals('Some title', (string)$view->render());
    }

    /**
     * @test
     */
    public function withPropertyFallback()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/propertyFallback');

        $view->assign('node', $this->textNode);

        self::assertEquals('Some text', (string)$view->render());
    }

    /**
     * @test
     */
    public function withPrefixOverrideAndPostfix()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/withPrefixOverrideAndPostfix');

        $view->assign('node', $this->textNode);

        self::assertEquals('Hello world how are you', (string)$view->render());
    }

    /**
     * @test
     */
    public function nodeTypeFallback()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/nodeTypeFallback');

        $view->assign('node', $this->textNode);

        self::assertEquals($this->textNode->nodeType->getLabel(), (string)$view->render());
    }

    /**
     * @test
     */
    public function crop()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/crop');

        $view->assign('node', $this->textNode);

        self::assertEquals('Some -', (string)$view->render());
    }

    protected function buildView(): TestingViewForFusionRuntime
    {
        $view = parent::buildView();

        $view->setPackageKey('Neos.Neos');
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');
        $view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

        return $view;
    }

    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped. Either migrate to behat or find a better way to mock node read models. See https://github.com/neos/neos-development-collection/issues/4317');
        parent::setUp();
        $nodeSubjectProvider = new NodeSubjectProvider();

        $nodeTypeName = NodeTypeName::fromString('Neos.Neos:Content.Text');
        // todo injecting the mocked nodeType in the node doesnt matter, as the nodeType is fetched from the nodeTypeManager in the NodeHelper
        $textNodeType = new NodeType(
            $nodeTypeName,
            [],
            [
                'ui' => [
                    'label' => 'Content.Text'
                ]
            ],
            new NodeTypeManager(
                fn () => [],
                new DefaultNodeLabelGeneratorFactory()
            ),
            new DefaultNodeLabelGeneratorFactory()
        );

        $textNodeProperties = new PropertyCollection(
            SerializedPropertyValues::fromArray([
                'title' => new SerializedPropertyValue(
                    'Some title',
                    'string'
                ),
                'text' => new SerializedPropertyValue(
                    'Some text',
                    'string'
                ),
            ]),
            $nodeSubjectProvider->propertyConverter
        );

        $now = new \DateTimeImmutable();

        $this->textNode = Node::create(
            ContentSubgraphIdentity::create(
                $contentRepositoryId,
                ContentStreamId::fromString("cs"),
                DimensionSpacePoint::createWithoutDimensions(),
                VisibilityConstraints::withoutRestrictions()
            ),
            NodeAggregateId::fromString("na"),
            OriginDimensionSpacePoint::createWithoutDimensions(),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            $nodeTypeName,
            $textNodeType,
            $textNodeProperties,
            null,
            NodeTags::createEmpty(),
            Timestamps::create($now, $now, null, null)
        );
    }
}
