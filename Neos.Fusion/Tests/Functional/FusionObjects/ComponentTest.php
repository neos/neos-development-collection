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
 * Testcase for the Component Fusion object
 *
 */
class ComponentTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function componentBasicRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/basicRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentNestedRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/nestedRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentStaticRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/staticRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentSandboxRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/sandboxRenderer');
        self::assertEquals('Hello ', $view->render());
    }

    /**
     * @test
     */
    public function componentLazyRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/lazyRenderer');
        self::assertEquals('Hello', $view->render());
    }

    /**
     * @test
     */
    public function componentWrapperRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/wrapperRenderer');
        self::assertEquals('Default content', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivate()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.Private>');
        self::assertEquals('MoinMoin!<div>Moin</div>', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateLazy()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateLazy>');
        self::assertEquals('MoinMoin!', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateSelfReferencing()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateSelfReferencing>');
        self::assertEquals('Moin!Moin!', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateSelfReferencingInfiniteLoop()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1669654158);
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateSelfReferencingInfiniteLoop>');
        $view->render();
    }

    /**
     * @test
     */
    public function componentPrivateSelfReferencingInfiniteLoopComplex()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1669654158);
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateSelfReferencingInfiniteLoopComplex>');
        $view->render();
    }

    /**
     * @test
     */
    public function componentPrivateCannotBeApplied()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateCannotBeApplied>');
        self::assertEquals('', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateCannotBeLooped()
    {
        $this->expectException(\TypeError::class);
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateCannotBeLooped>');
        $view->render();
    }

    /**
     * @test
     */
    public function componentPrivateScopeIsIsolatedFromOtherPrivate()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateScopeIsIsolatedFromOtherPrivate>');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateScopeIsIsolatedInRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateScopeIsIsolatedInRenderer>');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateScopeIsReachableInProps()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateScopeIsReachableInProps>');
        self::assertEquals("Moin", $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateLegacyPatternWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateLegacyPatternWorks>');
        self::assertEquals('Moin', $view->render());
    }

    /**
     * @test
     */
    public function componentPrivateOuterContextIsOverridden()
    {
        $view = $this->buildView();
        $view->setFusionPath('/<Neos.Fusion:Component.PrivateOuterContextIsOverridden>');
        self::assertSame("private props [/<Neos.Fusion:Component.PrivateOuterContextIsOverridden>/__meta/private]", $view->render());
    }
}
