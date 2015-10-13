<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
