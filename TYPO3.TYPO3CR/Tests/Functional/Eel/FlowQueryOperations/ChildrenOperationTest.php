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
 * Functional test case which tests FlowQuery ChildrenOperation
 */
class ChildrenOperationTest extends AbstractNodeTest
{
    /**
     * @test
     */
    public function noFilterReturnsAllChildNodes()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('')->get();
        $this->assertEquals(5, count($foundNodes));
    }

    /**
     * @test
     */
    public function propertyNameFilterIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('teaser')->get(0);
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x')->get(0);
        $this->assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multiplePropertyNameFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('teaser, sidebar')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('teaser, x')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x, sidebar')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x, y')->get();
        $this->assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function pathFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('teaser/dummy42, sidebar')->get();
        $this->assertEquals(2, count($foundNodes));
    }

    /**
     * @test
     */
    public function attributeFilterIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[title]')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('[x]')->get();
        $this->assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multipleAttributeFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[title][title != ""]')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('[title][title *= "Products"]')->get();
        $this->assertEquals(1, count($foundNodes));
    }

    /**
     * @test
     */
    public function instanceofFilterIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[instanceof Neos.ContentRepository.Testing:Page]')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('[instanceof Neos.ContentRepository.Testing:ContentCollection]')->get();
        $this->assertEquals(3, count($foundNodes));
    }

    /**
     * @test
     */
    public function twoInstanceofFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[instanceof Neos.ContentRepository.Testing:Document][instanceof Neos.ContentRepository.Testing:Page]')->get();
        $this->assertEquals(2, count($foundNodes));
    }

    /**
     * @test
     */
    public function multipleInstanceofFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[instanceof Neos.ContentRepository.Testing:Page], [instanceof Neos.ContentRepository.Testing:ContentCollection]')->get();
        $this->assertEquals(5, count($foundNodes));
    }

    /**
     * @test
     */
    public function negatedInstanceofFilterIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[instanceof !Neos.ContentRepository.Testing:ContentCollection]')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('[instanceof !Neos.ContentRepository.Testing:Page]')->get();
        $this->assertEquals(3, count($foundNodes));
    }

    /**
     * @test
     */
    public function twoNegatedInstanceofFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('[instanceof !Neos.ContentRepository.Testing:Page][instanceof !Neos.ContentRepository.Testing:ContentCollection]')->get();
        $this->assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function combinedFilterIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"]')->get();
        $this->assertEquals(0, count($foundNodes));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:X][title *= "Products"]')->get();
        $this->assertEquals(0, count($foundNodes));
        $foundNodes = $q->children('x[instanceof Neos.ContentRepository.Testing:Page][title *= "X"]')->get();
        $this->assertEquals(0, count($foundNodes));
    }

    /**
     * @test
     */
    public function multipleCombinedFiltersIsSupported()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"], about-us[instanceof Neos.ContentRepository.Testing:Page][title *= "About Us"]')->get();
        $this->assertEquals(2, count($foundNodes));
        $foundNodes = $q->children('x[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"], about-us[instanceof Neos.ContentRepository.Testing:Page][title *= "About Us"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:X][title *= "Products"], about-us[instanceof Neos.ContentRepository.Testing:Page][title *= "About Us"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('x[instanceof Neos.ContentRepository.Testing:Page][title *= "X"], about-us[instanceof Neos.ContentRepository.Testing:Page][title *= "About Us"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"], x[instanceof Neos.ContentRepository.Testing:Page][title *= "About Us"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"], about-us[instanceof Neos.ContentRepository.Testing:X][title *= "About Us"]')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNodes = $q->children('products[instanceof Neos.ContentRepository.Testing:Page][title *= "Products"], about-us[instanceof Neos.ContentRepository.Testing:Page][title *= "X"]')->get();
        $this->assertEquals(1, count($foundNodes));
    }
}
