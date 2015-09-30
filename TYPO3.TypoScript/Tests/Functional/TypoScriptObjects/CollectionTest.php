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
 * Testcase for the CollectionTest Array
 *
 */
class CollectionTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicCollectionWorks()
    {
        $view = $this->buildView();
        $view->assign('collection', array('element1', 'element2'));
        $view->setTypoScriptPath('collection/basicLoop');
        $this->assertEquals('Xelement1Xelement2', $view->render());
    }

    /**
     * @test
     */
    public function basicCollectionWorksAndStillContainsOtherContextVariables()
    {
        $view = $this->buildView();
        $view->assign('collection', array('element1', 'element2'));
        $view->assign('other', 'var');
        $view->setTypoScriptPath('collection/basicLoopOtherContextVariables');
        $this->assertEquals('Xelement1varXelement2var', $view->render());
    }

    /**
     * @test
     */
    public function emptyCollectionReturnsEmptyString()
    {
        $view = $this->buildView();
        $view->assign('collection', null);
        $view->setTypoScriptPath('collection/basicLoop');
        $this->assertEquals('', $view->render());
    }

    /**
     * @test
     */
    public function iterationInformationIsAddedToCollection()
    {
        $view = $this->buildView();
        $view->assign('collection', array('element1', 'element2', 'element3', 'element4'));
        $view->setTypoScriptPath('collection/iteration');
        $this->assertEquals('Xelement1-0-1-1--1-Xelement2-1-2----1Xelement3-2-3---1-Xelement4-3-4--1--1', $view->render());
    }
}
