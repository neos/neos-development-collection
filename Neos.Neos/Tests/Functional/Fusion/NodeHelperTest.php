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

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\Fusion\Tests\Functional\FusionObjects\AbstractFusionObjectTest;
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

    protected function buildView()
    {
        $view = parent::buildView();

        $view->setPackageKey('Neos.Neos');
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');
        $view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

        return $view;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $nodeType = $this
            ->getMockBuilder(NodeType::class)
            ->setMethods(['getName', 'getLabel'])
            ->disableOriginalConstructor()
            ->getMock();
        $nodeType
            ->method('getName')
            ->willReturn('Neos.Neos:Content.Text');
        $nodeType
            ->method('getLabel')
            ->willReturn('Content.Text');

        $textNode = $this
            ->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $textNode
            ->method('hasProperty')
            ->willReturnCallback(function ($arg) {
                return $arg === 'title' || $arg === 'text';
            });
        $textNode
            ->method('getProperty')
            ->willReturnCallback(function ($arg) {
                if ($arg === 'title') {
                    return 'Some title';
                }
                if ($arg === 'text') {
                    return 'Some text';
                }
                return null;
            });
        $textNode
            ->method('getNodeType')
            ->willReturn($nodeType);

        $this->textNode = $textNode;
    }
}
