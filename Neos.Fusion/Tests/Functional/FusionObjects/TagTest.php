<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Tag object
 *
 */
class TagTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function tagWithAttributesFromNonTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/plainAttributes');
        $this->assertSame('<link rel="stylesheet" type="text/css" />', $view->render());
    }

    /**
     * @test
     */
    public function tagWithAttributesFromTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/objectAttributes');
        $this->assertSame('<test sum="4" />', $view->render());
    }

    /**
     * @test
     */
    public function tagWithAttributesFromArraysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/arrayAttributes');
        $this->assertSame('<div class="a b"></div>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithContentFromNonTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/plainContent');
        $this->assertSame('<span>test</span>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithContentFromTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/objectContent');
        $this->assertSame('<span>4</span>', $view->render());
    }

    /**
     * @test
     */
    public function registeredSelfClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/registeredSelfClosingTag');
        $this->assertSame('<br />', $view->render());
    }

    /**
     * @test
     */
    public function omitClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/omitClosingTag');
        $this->assertSame('<test>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithEelExpressionUsingThis()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/withThis');
        $this->assertSame('<title databar="baz" datafoo="baz_baz">foo</title>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithIgnorePropertiesInAttributes()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/withIgnorePropertiesInAttributes');
        $this->assertSame('<title datafoo="baz_baz">foo</title>', $view->render());
    }
}
