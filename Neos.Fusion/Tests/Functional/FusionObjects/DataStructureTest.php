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

use Neos\Fusion\Exception\MissingFusionImplementationException;

/**
 * Testcase for the Fusion Dictionary
 */
class DataStructureTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/basicOrdering');
        self::assertEquals([10 => 'Xtest10', 100 => 'Xtest100'], $view->render());
    }

    /**
     * @test
     */
    public function positionalOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/positionalOrdering');
        self::assertEquals(['c' => 'Xbefore', 'f' => 'Xmiddle', 'a' => 'Xafter'], $view->render());
    }

    /**
     * @test
     */
    public function startEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/startEndOrdering');
        self::assertEquals(['c' => 'Xbefore', 'f' => 'Xmiddle', 'a' => 'Xafter'], $view->render());
    }

    /**
     * @test
     */
    public function advancedStartEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/advancedStartEndOrdering');
        self::assertEquals(['e' => 'Xe', 'd' => 'Xd', 'foobar' => 'Xfoobar', 'f' => 'Xf', 'g' => 'Xg', 100 => 'X100', 'b' => 'Xb', 'a' => 'Xa', 'c' => 'Xc'], $view->render());
    }

    /**
     * @test
     */
    public function ignoredPropertiesWork()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/ignoreProperties');
        self::assertEquals(['c' => 'Xbefore', 'a' => 'Xafter'], $view->render());
    }

    /**
     * @test
     */
    public function nestedKeysWithoutObjectTypesRenderAsDataStructure(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/nestingWithAndWithoutObjectName');
        self::assertEquals(['keyWithoutType' => ['bar' => ['baz' => 123 ]], 'keyWithType' => 456, 'keyWithValue' => 789], $view->render());
    }

    /**
     * @test
     */
    public function nestingWithNonExistingChildObjectThrowsException(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/nestingWithNonExistingChildObject');

        $this->expectException(MissingFusionImplementationException::class);

        $view->render();
    }

    /**
     * @test
     */
    public function untypedChildKeysWorkWithFusionIf(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/untypedChildKeysWithIf');
        self::assertEquals(['keyWithoutType' => ['foo' => 123]], $view->render());
    }

    /**
     * @test
     */
    public function untypedChildKeysWorkWithFusionProcess(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/untypedChildKeysWithProcess');
        self::assertEquals(['keyWithoutType' => ['foo' => 123, 0 => 'baz']], $view->render());
    }

    /**
     * @test
     */
    public function untypedChildKeysWorkWithFusionEelThisContext(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/untypedChildKeysWithThisContext');
        self::assertEquals(['keyWithoutType' => ['foo' => 123, 'thisFoo' => 123]], $view->render());
    }

    /**
     * @test
     */
    public function untypedChildKeysWorkWithFusionPositionSorting(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/untypedChildKeysWithPositionOrdering');
        self::assertEquals(['keyWithoutTypeLast' => ['baz' => 456], 'keyWithoutTypeFirst' => ['foo' => 123]], $view->render());
    }

    /**
     * @test
     */
    public function unsetChildKeyWillNotRender(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/unsetChildKeyWillNotRender');
        self::assertEquals(['foo' => 'bar'], $view->render());
    }

    /**
     * @test
     */
    public function unsetUntypedChildKeyWillNotRender(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/unsetUntypedChildKeyWillNotRender');
        self::assertEquals(['buz' => 456], $view->render());
    }

    /**
     * @test
     */
    public function nulledChildKeyWillRenderAsNull(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/nulledChildKeyWillRenderAsNull');
        self::assertEquals(['foo' => 'bar', 'null2' => null], $view->render());
    }

    /**
     * @test
     */
    public function appliedNullValueWillRenderAsNull(): void
    {
        $view = $this->buildView();
        $view->setFusionPath('dataStructure/appliedNullValueWillRenderAsNull');
        self::assertEquals(['nullAttribute' => null], $view->render());
    }
}
