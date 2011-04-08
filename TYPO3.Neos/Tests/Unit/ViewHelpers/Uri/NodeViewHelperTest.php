<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Tests\Unit\ViewHelpers\Uri;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Uri Node view helper
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeViewHelperTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\TYPO3\ViewHelpers\Uri\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$this->uriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$this->controllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
		$this->tagBuilder = $this->getMock('F3\Fluid\Core\ViewHelper\TagBuilder');
		$this->viewHelperVariableContainer = $this->getMock('F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer', array(), array(), '', FALSE);
		$this->viewHelper = $this->getAccessibleMock('F3\TYPO3\ViewHelpers\Link\NodeViewHelper', array('renderChildren'));
		$this->viewHelper->setControllerContext($this->controllerContext);
		$this->viewHelper->injectTagBuilder($this->tagBuilder);
		$this->viewHelper->setViewHelperVariableContainer($this->viewHelperVariableContainer);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderWithoutNodeGeneratesUriToCurrentNode() {
		$currentNode = $this->getMock('F3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$context = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$context->expects($this->once())->method('getCurrentNode')->will($this->returnValue($currentNode));

		$this->request->expects($this->once())->method('getFormat')->will($this->returnValue('xml.rss'));

		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('F3\TYPO3', 'contentContext')->will($this->returnValue($context));

		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(FALSE)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setFormat')->with('xml.rss')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with(NULL, array('node' => $currentNode))->will($this->returnValue('http://someuri/path'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://someuri/path');
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('http://someuri/path'));

		$output = $this->viewHelper->render();
		$this->assertEquals('http://someuri/path', $output);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderWithNodeGeneratesUriToGivenNode() {
		$node = $this->getMock('F3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);

		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(FALSE)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setFormat')->with(NULL)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with(NULL, array('node' => $node))->will($this->returnValue('http://someuri/path'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://someuri/path');
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('http://someuri/path'));

		$output = $this->viewHelper->render($node);
		$this->assertEquals('http://someuri/path', $output);
	}

}
?>