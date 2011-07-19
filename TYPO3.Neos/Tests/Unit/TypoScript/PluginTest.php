<?php
namespace TYPO3\TYPO3\Tests\Unit\TypoScript;

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
 * Testcase for the Plugin TypoScript object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PluginTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Plugin
	 */
	protected $plugin;

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * @var \TYPO3\TypoScript\RenderingContext
	 */
	protected $mockRenderingContext;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Response
	 */
	protected $mockResponse;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\FLOW3\MVC\Dispatcher
	 */
	protected $mockDispatcher;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\SubRequestBuilder
	 */
	protected $mockSubRequestBuilder;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$this->mockSubRequestBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequestBuilder');
		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->mockDispatcher = $this->getMock('TYPO3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$this->mockResponse = $this->getMock('TYPO3\FLOW3\MVC\Web\Response');
		$this->mockControllerContext = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
		$this->mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($this->mockResponse));
		$this->mockRenderingContext = $this->getMock('TYPO3\TypoScript\RenderingContext');
		$this->mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
		$this->plugin = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Plugin', array('getPluginNamespace'));
		$this->plugin->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('typo3_plugin_namespace'));
		$this->plugin->setRenderingContext($this->mockRenderingContext);
		$this->plugin->setNode($this->mockNode);
		$this->plugin->_set('subRequestBuilder', $this->mockSubRequestBuilder);
		$this->plugin->_set('objectManager', $this->mockObjectManager);
		$this->plugin->_set('dispatcher', $this->mockDispatcher);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRenderingContextThrowsExceptionIfRenderingContextIsNoTypoScriptRenderingContext() {
		$mockRenderingContext = $this->getMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
		$this->plugin->setRenderingContext($mockRenderingContext);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCreatesAndDispatchesSubRequestAndReturnItsContent() {
		$this->markTestIncomplete('Needs to have a new check for the actual response');
#		$mockPluginResponse = $this->getMock('TYPO3\FLOW3\MVC\Web\SubResponse', array(), array(), '', FALSE);
#		$mockPluginResponse->expects($this->atLeastOnce())->method('getContent')->will($this->returnValue($expectedResult));
#		$this->mockObjectManager->expects($this->once())->method('create')->with('TYPO3\FLOW3\MVC\Web\SubResponse', $this->mockResponse)->will($this->returnValue($mockPluginResponse));

		$expectedResult = 'pluginResponse content';
		$mockPluginRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$mockPluginRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('Foo'));
		$mockPluginRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('Bar'));
		$mockPluginRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('Baz'));
		$mockPluginRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('quux'));

		$this->mockSubRequestBuilder->expects($this->once())->method('build')->with($this->mockRequest, 'typo3_plugin_namespace')->will($this->returnValue($mockPluginRequest));
		$this->mockDispatcher->expects($this->once())->method('dispatch')->with($mockPluginRequest);

		$actualResult = $this->plugin->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderSetsControllerActionInformationOnRequestObjectIfItHasBeenDefinedInThePluginProperties() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$mockNode->expects($this->any())->method('getProperty')->will($this->returnValue(NULL));

		$mockPluginRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue(NULL));

		$this->mockSubRequestBuilder->expects($this->once())->method('build')->with($this->mockRequest, 'typo3_plugin_namespace')->will($this->returnValue($mockPluginRequest));

		$this->plugin->setNode($mockNode);

		$this->plugin->setPackage('SomePackageKey');
		$this->plugin->setSubpackage('SomeSubpackageKey');
		$this->plugin->setController('SomeController');
		$this->plugin->setAction('someAction');

		$mockPluginRequest->expects($this->once())->method('setControllerPackageKey')->with('SomePackageKey');
		$mockPluginRequest->expects($this->once())->method('setControllerSubpackageKey')->with('SomeSubpackageKey');
		$mockPluginRequest->expects($this->once())->method('setControllerName')->with('SomeController');
		$mockPluginRequest->expects($this->once())->method('setControllerActionName')->with('someAction');

		$this->plugin->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderDoesNotSetRequestPackageKeyIfItIsAlreadySet() {
		$mockPluginRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('Foo'));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue('Bar'));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('Baz'));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('quux'));

		$this->mockSubRequestBuilder->expects($this->once())->method('build')->with($this->mockRequest, 'typo3_plugin_namespace')->will($this->returnValue($mockPluginRequest));

		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('SomePackage'));
		$mockPluginRequest->expects($this->never())->method('setControllerPackageKey');

		$this->plugin->render();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderSetsControllerActionInformationOnRequestObjectIfItHasBeenDefinedInTheNode() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$mockNode->expects($this->at(0))->method('getProperty')->with('package')->will($this->returnValue('PackageDefinedInNode'));
		$mockNode->expects($this->at(1))->method('getProperty')->with('subpackage')->will($this->returnValue('SubpackageDefinedInNode'));
		$mockNode->expects($this->at(2))->method('getProperty')->with('controller')->will($this->returnValue('ControllerDefinedInNode'));
		$mockNode->expects($this->at(3))->method('getProperty')->with('action')->will($this->returnValue('actionDefinedInNode'));

		$mockPluginRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue(NULL));
		$mockPluginRequest->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue(NULL));

		$this->mockSubRequestBuilder->expects($this->once())->method('build')->with($this->mockRequest, 'typo3_plugin_namespace')->will($this->returnValue($mockPluginRequest));

		$this->plugin->setNode($mockNode);

		$mockPluginRequest->expects($this->once())->method('setControllerPackageKey')->with('PackageDefinedInNode');
		$mockPluginRequest->expects($this->once())->method('setControllerSubpackageKey')->with('SubpackageDefinedInNode');
		$mockPluginRequest->expects($this->once())->method('setControllerName')->with('ControllerDefinedInNode');
		$mockPluginRequest->expects($this->once())->method('setControllerActionName')->with('actionDefinedInNode');

		$this->plugin->render();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPluginNamespaceReturnsTheNodesValueIfItIsSetThere() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$mockNode->expects($this->any())->method('getProperty')->with('argumentNamespace')->will($this->returnValue('someArgumentNamespace'));

		$plugin = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Plugin', array('dummy'));
		$plugin->setNode($mockNode);
		$plugin->setArgumentNamespace('someDefaultArgumentNamespace');

		$this->assertEquals($plugin->_call('getPluginNamespace'), 'someArgumentNamespace');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPluginNamespaceReturnsTheNamespaceSetInThePluginClassIfNoneIsSetInTheNode() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$mockNode->expects($this->any())->method('getProperty')->with('argumentNamespace')->will($this->returnValue(NULL));

		$plugin = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Plugin', array('dummy'));
		$plugin->setNode($mockNode);
		$plugin->setArgumentNamespace('someDefaultArgumentNamespace');

		$this->assertEquals($plugin->_call('getPluginNamespace'), 'someDefaultArgumentNamespace');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPluginNamespaceCompilesTheNamespaceFromTheClassNameIfNoneIsSetInThePluginClassNorInTheNode() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$mockNode->expects($this->any())->method('getProperty')->with('argumentNamespace')->will($this->returnValue(NULL));

		$plugin = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Plugin', array('dummy'));
		$plugin->setNode($mockNode);

		$this->assertEquals($plugin->_call('getPluginNamespace'), strtolower(str_replace('\\', '_', get_class($plugin))));
	}

}
?>