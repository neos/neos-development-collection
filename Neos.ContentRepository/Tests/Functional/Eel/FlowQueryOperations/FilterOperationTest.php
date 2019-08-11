<?php
namespace Neos\ContentRepository\Tests\Functional\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery FilterOperation
 */
class FilterOperationTest extends AbstractNodeTest
{
    /**
     * @test
     */
    public function noFilterReturnsAllNodesInContext()
    {
        $q = new FlowQuery([$this->node, $this->node->getNode('products')]);
        $foundNodes = $q->filter('')->get();
        self::assertEquals(2, count($foundNodes));
    }

    /**
     * @test
     */
    public function filterByNodeObjectIsSupported()
    {
        $q = new FlowQuery([$this->node, $this->node->getNode('products')]);
        $foundNodes = $q->filter($this->node)->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
    }

    /**
     * @test
     */
    public function propertyNameFilterIsSupported()
    {
        $q = new FlowQuery([$this->node, $this->node->getNode('products')]);
        $foundNodes = $q->filter('home')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multiplePropertyNameFiltersIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $q = new FlowQuery([$this->node, $productsNode]);
        $foundNodes = $q->filter('home, products')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('home, x')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('x, products')->get();
        self::assertSame($productsNode, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('x, x')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function identityFilterIsSupported()
    {
        $q = new FlowQuery([$this->node, $this->node->getNode('products')]);
        $foundNodes = $q->filter('#3239baee-3e7f-785c-0853-f4302ef32570')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('#xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multipleIdentityFiltersIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $q = new FlowQuery([$this->node, $productsNode]);
        $foundNodes = $q->filter('#3239baee-3e7f-785c-0853-f4302ef32570, #25eaba22-b8ed-11e3-a8b5-c82a1441d728')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('#3239baee-3e7f-785c-0853-f4302ef32570, #xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('#xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx, #25eaba22-b8ed-11e3-a8b5-c82a1441d728')->get();
        self::assertSame($productsNode, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('#xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx, #xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function attributeFilterUsingPropertyIsSupported()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->filter('[title *= "Home"]')->get();
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('[title *= "x"]')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function attributeFilterUsingInternalPropertyIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $q = new FlowQuery([$this->node, $productsNode]);
        $foundNodes = $q->filter('[_depth = 3]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('[_depth = 4]')->get();
        self::assertSame($productsNode, $foundNodes[0]);
        self::assertEquals(1, count($foundNodes));
        $foundNodes = $q->filter('[_depth = 5]')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function instanceofFilterUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $q = new FlowQuery([$this->node, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[instanceof Neos.ContentRepository.Testing:Page]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[instanceof Neos.ContentRepository.Testing:ContentCollection]')->get();
        self::assertSame($teaserNode, $foundNodes[0]);
        self::assertSame($sidebarNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[instanceof X]')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function notInstanceofFilterUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $dummy = $sidebarNode->getNode('dummy43');
        $q = new FlowQuery([$this->node, $dummy, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[!instanceof Neos.ContentRepository.Testing:Html]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertSame($teaserNode, $foundNodes[2]);
        self::assertSame($sidebarNode, $foundNodes[3]);
        self::assertEquals(4, count($foundNodes));
        $foundNodes = $q->filter('[!instanceof Neos.ContentRepository.Testing:ContentCollection]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($dummy, $foundNodes[1]);
        self::assertSame($productsNode, $foundNodes[2]);
        self::assertEquals(3, count($foundNodes));
        $foundNodes = $q->filter('[!instanceof X]')->get();
        self::assertEquals(5, count($foundNodes));
    }

    /**
     * @test
     */
    public function twoInstanceofFiltersUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $q = new FlowQuery([$this->node, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[instanceof Neos.ContentRepository.Testing:Document][instanceof Neos.ContentRepository.Testing:Page]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[instanceof X][instanceof Neos.ContentRepository.Testing:Page]')->get();
        self::assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multipleInstanceofFiltersUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $q = new FlowQuery([$this->node, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[instanceof Neos.ContentRepository.Testing:Page], [instanceof Neos.ContentRepository.Testing:ContentCollection]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertSame($teaserNode, $foundNodes[2]);
        self::assertSame($sidebarNode, $foundNodes[3]);
        self::assertEquals(4, count($foundNodes));
    }

    /**
     * @test
     */
    public function negatedInstanceofFilterUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $q = new FlowQuery([$this->node, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[instanceof !Neos.ContentRepository.Testing:ContentCollection]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[instanceof !Neos.ContentRepository.Testing:Page]')->get();
        self::assertSame($teaserNode, $foundNodes[0]);
        self::assertSame($sidebarNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[instanceof !X]')->get();
        self::assertEquals(4, count($foundNodes));
    }

    /**
     * @test
     */
    public function doubleNegatedInstanceofFilterUsingNodeTypeIsSupported()
    {
        $productsNode = $this->node->getNode('products');
        $teaserNode = $this->node->getNode('teaser');
        $sidebarNode = $this->node->getNode('sidebar');
        $q = new FlowQuery([$this->node, $productsNode, $teaserNode, $sidebarNode]);
        $foundNodes = $q->filter('[!instanceof !Neos.ContentRepository.Testing:Page]')->get();
        self::assertSame($this->node, $foundNodes[0]);
        self::assertSame($productsNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[!instanceof !Neos.ContentRepository.Testing:ContentCollection]')->get();
        self::assertSame($teaserNode, $foundNodes[0]);
        self::assertSame($sidebarNode, $foundNodes[1]);
        self::assertEquals(2, count($foundNodes));
        $foundNodes = $q->filter('[!instanceof !X]')->get();
        self::assertEquals(0, count($foundNodes));
    }
}
