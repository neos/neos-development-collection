<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(FLOW_PATH_PACKAGES . 'Framework/TYPO3.Fluid/Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php');

use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\ViewHelpers\ContentElement\EditableViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * Test for the contentElement.editable ViewHelper
 */
class EditableViewHelperTest extends ViewHelperBaseTestcase {

	/**
	 * @var EditableViewHelper
	 */
	protected $editableViewHelper;

	/**
	 * @var PrivilegeManagerInterface
	 */
	protected $mockPrivilegeManager;

	/**
	 * @var TemplateImplementation
	 */
	protected $mockTemplateImplementation;

	/**
	 * @var Runtime
	 */
	protected $mockTsRuntime;

	/**
	 * @var array
	 */
	protected $mockTsContext;

	/**
	 * @var NodeInterface
	 */
	protected $mockNode;

	/**
	 * @var ContentContext
	 */
	protected $mockContentContext;

	/**
	 * @var FluidView
	 */
	protected $mockView;

	/**
	 * @var array
	 */
	protected $templateVariables = array();

	public function setUp() {
		parent::setUp();
		$this->editableViewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\ContentElement\EditableViewHelper', array('renderChildren'));

		$this->mockPrivilegeManager = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface')->getMock();
		$this->inject($this->editableViewHelper, 'privilegeManager', $this->mockPrivilegeManager);

		$this->mockTemplateImplementation = $this->getMockBuilder('TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation')->disableOriginalConstructor()->getMock();

		$this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();

		$this->mockContentContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();

		$this->mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContentContext));

		$this->mockTsContext = array('node' => $this->mockNode);
		$this->mockTsRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue($this->mockTsContext));
		$this->mockTemplateImplementation->expects($this->any())->method('getTsRuntime')->will($this->returnValue($this->mockTsRuntime));
		$this->mockView = $this->getAccessibleMock('TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView', array(), array(), '', FALSE);
		$this->mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($this->mockTemplateImplementation));

		$this->editableViewHelper->initializeArguments();
	}

	/**
	 * @param AbstractViewHelper $viewHelper
	 * @return void
	 */
	protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper) {
		parent::injectDependenciesIntoViewHelper($viewHelper);
		$templateVariables = $this->templateVariables;
		$this->templateVariableContainer->expects($this->any())->method('exists')->will($this->returnCallback(function($variableName) use ($templateVariables) {
			return isset($templateVariables[$variableName]);
		}));
		$this->templateVariableContainer->expects($this->any())->method('get')->will($this->returnCallback(function($variableName) use ($templateVariables) {
			return $templateVariables[$variableName];
		}));
	}

	/**
	 * Mocks access to the TypoScriptObject
	 */
	protected function injectTypoScriptObject() {
		$this->viewHelperVariableContainer->expects($this->any())->method('getView')->will($this->returnValue($this->mockView));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionIfTheGivenPropertyIsNotAccessible() {
		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionIfTheTsTemplateObjectIsNotSet() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue',
		);
		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderSetsThePropertyValueAsTagContentIfItExists() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);
		$this->tagBuilder->expects($this->once())->method('setContent')->with('somePropertyValue');
		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderSetsTheChildNodesAsTagContentIfTheyAreSet() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);

		$this->editableViewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('overriddenPropertyValue'));
		$this->tagBuilder->expects($this->once())->method('setContent')->with('overriddenPropertyValue');
		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderDoesNotAddEditingMetaDataAttributesIfInLiveWorkspace() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);

		$this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('live'));
		$this->tagBuilder->expects($this->never())->method('addAttribute');

		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderDoesNotAddEditingMetaDataAttributesIfUserHasNoAccessToBackend() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);

		$this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
		$this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(FALSE));
		$this->tagBuilder->expects($this->never())->method('addAttribute');

		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasNoAccessToBackend() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);

		$this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
		$this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(TRUE));
		$this->tagBuilder->expects($this->atLeastOnce())->method('addAttribute');

		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->injectTypoScriptObject();
		$this->editableViewHelper->render('someProperty');
	}

	/**
	 * @test
	 */
	public function renderUsesTheNodeArgumentIfSet() {
		$this->templateVariables = array(
			'someProperty' => 'somePropertyValue'
		);

		$this->tagBuilder->expects($this->once())->method('render');

		$this->injectDependenciesIntoViewHelper($this->editableViewHelper);
		$this->editableViewHelper->render('someProperty', 'div', $this->mockNode);
	}

}
