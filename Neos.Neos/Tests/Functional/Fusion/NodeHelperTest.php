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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Fusion\Tests\Functional\FusionObjects\AbstractFusionObjectTestCase;
use Neos\Neos\Domain\Service\ContentContext;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the Fusion NodeLabel helper
 */
class NodeHelperTest extends AbstractFusionObjectTestCase
{
    /**
     * @var Node|MockObject
     */
    protected $textNode;

    #[Test]
    public function defaultNodeLabel()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/defaultLabel');

        $view->assign('node', $this->textNode);

        self::assertEquals('Some title', (string)$view->render());
    }

    #[Test]
    public function withPropertyFallback()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/propertyFallback');

        $view->assign('node', $this->textNode);

        self::assertEquals('Some text', (string)$view->render());
    }

    #[Test]
    public function withPrefixOverrideAndPostfix()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/withPrefixOverrideAndPostfix');

        $view->assign('node', $this->textNode);

        self::assertEquals('Hello world how are you', (string)$view->render());
    }

    #[Test]
    public function nodeTypeFallback()
    {
        $view = $this->buildView();
        $view->setFusionPath('nodeHelper/nodeTypeFallback');

        $view->assign('node', $this->textNode);

        self::assertEquals($this->textNode->getNodeType()->getLabel(), (string)$view->render());
    }

    #[Test]
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
            ->onlyMethods(['getName', 'getLabel'])
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
            ->onlyMethods(['hasProperty', 'getProperty', 'getNodeType', 'isAutoCreated', 'getContext'])
            ->disableOriginalConstructor()
            ->getMock();
        $textNode
            ->method('hasProperty')
            ->willReturnCallback(function ($arg) {
                return $arg === 'title' || $arg === 'text';
            });
        $textNode
            ->method('isAutoCreated')
            ->willReturn(false);
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

        $fakeContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();
        $textNode
            ->method('getContext')
            ->willReturn($fakeContext);

        $this->textNode = $textNode;
    }
}
