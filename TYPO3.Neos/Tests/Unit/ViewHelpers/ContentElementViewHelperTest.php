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

use TYPO3\Neos\ViewHelpers\ContentElementViewHelper;

require_once(FLOW_PATH_PACKAGES . 'Framework/TYPO3.Fluid/Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php');

/**
 * Test for ContentElementViewHelper
 */
class ContentElementViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\Neos\Service\ContentElementWrappingService
	 */
	protected $mockContentElementWrappingService;

	/**
	 * @var string
	 */
	protected $innerContent = '<span>Inner content</span>';

	/**
	 * @test
	 */
	public function renderWithTagAttributesOverridesWrappedTagBuilder() {
		$viewHelper = $this->getMockedViewHelper();

		$this->simulateWrappedTagBuilderAttributes(array(
			'class' => 'neos-content-element',
			'data-foo' => 'bar',
			'id' => 'c123245678'
		));

		$this->initializeViewHelperArguments($viewHelper, array(
			'class' => 'my-class',
			'additionalAttributes' => array(
				'data-custom' => 'bar'
			)
		));

		$output = $viewHelper->render($this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface'), 'ul');

		$this->assertEquals('<ul class="neos-content-element my-class" data-foo="bar" id="c123245678" data-custom="bar"><span>Inner content</span></ul>', $output);
	}

	/**
	 * @return ContentElementViewHelper
	 */
	protected function getMockedViewHelper() {
		/** @var ContentElementViewHelper $viewHelper */
		$viewHelper = $this->getMock('TYPO3\Neos\ViewHelpers\ContentElementViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue($this->innerContent));

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div');
		$this->inject($viewHelper, 'tag', $tagBuilder);

		$mockTemplateObject = $this->getMockBuilder('TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation')->disableOriginalConstructor()->getMock();
		$templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array(
			'fluidTemplateTsObject' => $mockTemplateObject
		));
		$this->inject($viewHelper, 'templateVariableContainer', $templateVariableContainer);

		$this->mockContentElementWrappingService = $this->getMock('TYPO3\Neos\Service\ContentElementWrappingService');
		$this->inject($viewHelper, 'contentElementWrappingService', $this->mockContentElementWrappingService);

		return $viewHelper;
	}

	/**
	 * @param array $attributesFromWrappedContentElement
	 */
	protected function simulateWrappedTagBuilderAttributes(array $attributesFromWrappedContentElement) {
		$wrappedTagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div', $this->innerContent);
		$wrappedTagBuilder->addAttributes($attributesFromWrappedContentElement);
		$this->mockContentElementWrappingService->expects($this->any())->method('wrapContentObjectAndReturnTagBuilder')->will($this->returnValue($wrappedTagBuilder));
	}

	/**
	 * @param ContentElementViewHelper $viewHelper
	 * @param array $arguments
	 */
	protected function initializeViewHelperArguments(ContentElementViewHelper $viewHelper, array $arguments) {
		$viewHelper->initializeArguments();
		$viewHelper->setArguments($arguments);
		$viewHelper->initialize();
	}

}
