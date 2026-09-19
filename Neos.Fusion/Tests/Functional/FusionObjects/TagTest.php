<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects;

use PHPUnit\Framework\Attributes\Test;

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
class TagTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function tagWithAttributesFromNonFusionObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/plainAttributes');
        self::assertSame('<link rel="stylesheet" type="text/css" />', $view->render());
    }

    #[Test]
    public function tagWithAttributesFromFusionObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/objectAttributes');
        self::assertSame('<test sum="4" />', $view->render());
    }

    #[Test]
    public function tagWithAttributesFromArraysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/arrayAttributes');
        self::assertSame('<div class="a b"></div>', $view->render());
    }

    #[Test]
    public function tagWithFusionAttributesWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/fusionAttributes');
        self::assertSame('<div key="value" list="foo bar"></div>', $view->render());
    }

    #[Test]
    public function tagWithAttributesFromDataStructureWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/dataStructureAttributes');
        self::assertSame('<div key="value" list="foo bar"></div>', $view->render());
    }

    #[Test]
    public function tagWithBooleanAttributesWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/booleanAttributes');
        self::assertSame('<div foo></div>', $view->render());
    }

    #[Test]
    public function tagWithBooleanAttributesAndForbiddenEmptyAttributesWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/booleanAttributesAndForbiddenEmptyAttributes');
        self::assertSame('<div foo=""></div>', $view->render());
    }

    #[Test]
    public function tagWithBooleanAndAllowEmptyAttributesAttributesWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/booleanAttributesAndAllowEmptyAttributes');
        self::assertSame('<div foo></div>', $view->render());
    }

    #[Test]
    public function tagWithContentFromNonFusionObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/plainContent');
        self::assertSame('<span>test</span>', $view->render());
    }

    #[Test]
    public function tagWithContentFromFusionObjectWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/objectContent');
        self::assertSame('<span>4</span>', $view->render());
    }

    #[Test]
    public function registeredSelfClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/registeredSelfClosingTag');
        self::assertSame('<br />', $view->render());
    }

    #[Test]
    public function omitClosingTagWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/omitClosingTag');
        self::assertSame('<test>', $view->render());
    }

    #[Test]
    public function tagWithEelExpressionUsingThis()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/withThis');
        self::assertSame('<title databar="baz" datafoo="baz_baz">foo</title>', $view->render());
    }

    #[Test]
    public function tagWithIgnorePropertiesInAttributes()
    {
        $view = $this->buildView();
        $view->setFusionPath('tag/withIgnorePropertiesInAttributes');
        self::assertSame('<title datafoo="baz_baz">foo</title>', $view->render());
    }
}
