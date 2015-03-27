<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\Neos\ViewHelpers\Link\NodeViewHelper;

/**
 * Testcase for the Link.Node view helper
 *
 */
class NodeViewHelperTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function renderUsesUriNodeViewHelperToBuildTheUri() {
		/** @var NodeViewHelper $viewHelper */
		$viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\Link\NodeViewHelper', array('renderChildren', 'createUriNodeViewHelper'));

		$uriNodeViewHelper = $this->getMock('TYPO3\Neos\ViewHelpers\Uri\NodeViewHelper');
		$renderingContext = $this->getMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$tagBuilder = new TagBuilder('a');
		$this->inject($viewHelper, 'tag', $tagBuilder);
		$this->inject($viewHelper, 'renderingContext', $renderingContext);

		$viewHelper->expects($this->any())->method('createUriNodeViewHelper')->will($this->returnValue($uriNodeViewHelper));
		$viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('Link label'));

		$arguments = array('foo' => 'bar', 'baz' => 'Foos');
		$uriNodeViewHelper->expects($this->atLeastOnce())->method('render')->with($node, 'html', FALSE, 'otherNode', $arguments)->will($this->returnValue('some/other/page.html'));

		$output = $viewHelper->render($node, 'html', FALSE, 'otherNode', $arguments);

		$this->assertEquals('<a href="some/other/page.html">Link label</a>', $output);
	}


}
