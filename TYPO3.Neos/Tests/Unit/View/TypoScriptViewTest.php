<?php
namespace TYPO3\Neos\Tests\Unit\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript View
 *
 */
class TypoScriptViewTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @expectedException \TYPO3\Neos\Exception
	 * @test
	 */
	public function attemptToRenderWithoutNodeInformationAtAllThrowsException() {
		$view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('dummy'));
		$view->render();
	}

	/**
	 * @expectedException \TYPO3\Neos\Exception
	 * @test
	 */
	public function attemptToRenderWithInvalidNodeInformationThrowsException() {
		$view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('dummy'));
		$view->_set('variables', array('value' => 'foo'));
		$view->render();
	}

	/**
	 * @test
	 */
	public function renderPutsSiteNodeInTypoScriptContext() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface');
		$mockSiteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface');
		$mockContext = $this->getMock('TYPO3\Neos\Domain\Service\ContentContext', array(), array(), '', FALSE);

		$mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));

		$mockNodeRepository = $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		$mockNodeRepository->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

		$mockRuntime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array(), array(), '', FALSE);

		$mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);

		$mockTypoScriptService = $this->getMock('TYPO3\Neos\Domain\Service\TypoScriptService');
		$mockTypoScriptService->expects($this->any())->method('createRuntime')->will($this->returnValue($mockRuntime));

		$view = $this->getAccessibleMock('TYPO3\Neos\View\TypoScriptView', array('dummy'));

		$this->inject($view, 'nodeRepository', $mockNodeRepository);
		$this->inject($view, 'controllerContext', $mockControllerContext);
		$this->inject($view, 'typoScriptService', $mockTypoScriptService);

		$view->_set('variables', array('value' => $mockNode));

		$mockRuntime->expects($this->once())->method('pushContextArray')->with($this->arrayHasKey('site'));

		$view->render();
	}

}

?>