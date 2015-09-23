<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Tag object
 *
 */
class TagTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function tagWithAttributesFromNonTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/plainAttributes');
        $this->assertSame('<link rel="stylesheet" type="text/css" />', $view->render());
    }

    /**
     * @test
     */
    public function tagWithAttributesFromTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/objectAttributes');
        $this->assertSame('<test sum="4" />', $view->render());
    }

    /**
     * @test
     */
    public function tagWithAttributesFromArraysWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/arrayAttributes');
        $this->assertSame('<div class="a b"></div>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithContentFromNonTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/plainContent');
        $this->assertSame('<span>test</span>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithContentFromTsObjectWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/objectContent');
        $this->assertSame('<span>4</span>', $view->render());
    }

    /**
     * @test
     */
    public function registeredSelfClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/registeredSelfClosingTag');
        $this->assertSame('<br />', $view->render());
    }

    /**
     * @test
     */
    public function omitClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/omitClosingTag');
        $this->assertSame('<test>', $view->render());
    }

    /**
     * @test
     */
    public function tagWithEelExpressionUsingThis()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('tag/withThis');
        $this->assertSame('<title databar="baz" datafoo="baz_baz">foo</title>', $view->render());
    }
}
