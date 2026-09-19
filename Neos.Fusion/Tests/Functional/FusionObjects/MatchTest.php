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
 * Testcase for the Debug object
 *
 */
class MatchTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function matchEmptyValue()
    {
        $view = $this->buildView();
        $view->setFusionPath('match/empty');
        $result = $view->render();
        self::assertEquals('empty', $result);
    }

    #[Test]
    public function matchSimple()
    {
        $view = $this->buildView();
        $view->setFusionPath('match/foundMatch');
        $result = $view->render();
        self::assertEquals('module--left', $result);
    }

    #[Test]
    public function matchDefault()
    {
        $view = $this->buildView();
        $view->setFusionPath('match/default');
        $result = $view->render();
        self::assertEquals('module--centered', $result);
    }

    #[Test]
    public function errorWithoutMatch()
    {
        $this->expectExceptionMessage('Unhandled match');
        $view = $this->buildView();
        $view->setFusionPath('match/errorWithoutMatch');
        $view->render();
    }

    #[Test]
    public function matchDefaultDataStructure()
    {
        $view = $this->buildView();
        $view->setFusionPath('match/defaultDataStructure');
        $result = $view->render();
        self::assertEquals(['key' => 'value'], $result);
    }
}
