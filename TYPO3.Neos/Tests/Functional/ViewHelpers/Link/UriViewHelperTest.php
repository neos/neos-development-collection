<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Neos\ViewHelpers\Link\UriViewHelper;

/**
 * Functional tests for the UriViewHelper
 */
class UriViewHelperTest extends FunctionalTestCase
{

    protected static $testablePersistenceEnabled = true;

    /**
     * @var UriViewHelper
     */
    protected $viewHelper;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->objectManager->get('TYPO3\Neos\ViewHelpers\Link\UriViewHelper');
        $templateVariableContainer = new TemplateVariableContainer(array());
        $this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);
        $templateVariableContainer->add('content', 'Foo bar');
        $this->viewHelper->setRenderChildrenClosure(function () use ($templateVariableContainer) {
            return $templateVariableContainer->get('content');
        });
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function uriViewHelperRendersALinkToTheGivenUri()
    {
        $this->assertSame(
            '<a href="http://foor.bar" target="_blank">Foo bar</a>',
            $this->viewHelper->render('http://foor.bar')
        );
    }

    /**
     * @test
     */
    public function uriViewHelperRendersALinkToTheGivenUriAndCustomTarget()
    {
        $this->assertSame(
            '<a href="http://foor.bar" target="_top">Foo bar</a>',
            $this->viewHelper->render('http://foor.bar', '_top')
        );
    }

}