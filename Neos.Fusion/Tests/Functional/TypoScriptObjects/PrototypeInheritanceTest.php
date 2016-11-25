<?php
namespace Neos\Fusion\Tests\Functional\TypoScriptObjects;

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
 * Prototypical Inheritance Test
 */
class PrototypeInheritanceTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function baseClassHasModifiedValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritance/base');
        $this->assertEquals('BaseModified', $view->render());
    }

    /**
     * @test
     */
    public function subWithOverrideHasOverriddenValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritance/subWithOverride');
        $this->assertEquals('Sub', $view->render());
    }

    /**
     * @test
     */
    public function subWithoutOverrideHasModifiedBaseValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritance/subWithoutOverride');
        $this->assertEquals('BaseModified', $view->render());
    }

    /**
     * @test
     */
    public function advancedBaseObjectHasModifiedValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritanceAdvanced/base');
        $this->assertEquals('prepend_beforeOverride|value_from_nested_prototype|append_afterOverride', $view->render());
    }

    /**
     * @test
     */
    public function advancedSubWithoutOverrideHasModifiedBaseValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritanceAdvanced/subWithoutOverride');
        $this->assertEquals('prepend_beforeOverride|value_from_nested_prototype|append_afterOverride', $view->render());
    }

    /**
     * @test
     */
    public function advancedSubWithOverrideHasModifiedBaseValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritanceAdvanced/subWithOverride');
        $this->assertEquals('prepend_inSub|value_from_nested_prototype|append_afterOverride', $view->render());
    }

    /**
     * @test
     */
    public function contextDependentPrototypesTakeInheritanceIntoAccount()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('prototypeInheritanceContentDependent/element');
        $this->assertEquals('NEW VALUE in base class', $view->render());
    }
}
