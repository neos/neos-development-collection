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

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\Neos\Domain\Model\Site;

/**
 */
class NodeViewHelperTest extends \TYPO3\Neos\Tests\Functional\ViewHelpers\Uri\NodeViewHelperTest {

	/**
	 * @var \TYPO3\Neos\ViewHelpers\Link\NodeViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('TYPO3\Neos\ViewHelpers\Link\NodeViewHelper', array('renderChildren'));
		/** @var $requestHandler \TYPO3\Flow\Tests\FunctionalTestRequestHandler */
		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$controllerContext = new ControllerContext(new ActionRequest($requestHandler->getHttpRequest()), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder(), new FlashMessageContainer());
		$this->inject($this->viewHelper, 'controllerContext', $controllerContext);
		$this->inject($this->viewHelper, 'nodeRepository', $this->nodeRepository);
		$this->inject($this->viewHelper, 'tag', new TagBuilder());
	}

	/**
	 * Changes the original Uri Viewhelper's assertion to use the href attribute rather the actual output
	 * @param $expected
	 * @param $actual
	 */
	protected function assertOutputLinkValid($expected, $actual) {
		/** @var $tag TagBuilder */
		$tag = $this->viewHelper->_get('tag');
		parent::assertOutputLinkValid($expected, $tag->getAttribute('href'));
	}
}
?>