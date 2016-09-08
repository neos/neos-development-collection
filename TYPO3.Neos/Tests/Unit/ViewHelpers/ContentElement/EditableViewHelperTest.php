<?php
namespace TYPO3\Neos\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(FLOW_PATH_PACKAGES . 'Framework/TYPO3.Fluid/Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php');

use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\ViewHelpers\ContentElement\EditableViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\AuthorizationService;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * Test for the contentElement.editable ViewHelper
 */
class EditableViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var EditableViewHelper
     */
    protected $editableViewHelper;

    /**
     * @var PrivilegeManagerInterface
     */
    protected $mockPrivilegeManager;

    /**
     * @var AuthorizationService
     */
    protected $mockNodeAuthorizationService;

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

    public function setUp()
    {
        parent::setUp();
        $this->editableViewHelper = $this->getAccessibleMock(EditableViewHelper::class, array('renderChildren'));

        $this->mockPrivilegeManager = $this->getMockBuilder(PrivilegeManagerInterface::class)->getMock();
        $this->inject($this->editableViewHelper, 'privilegeManager', $this->mockPrivilegeManager);

        $this->mockNodeAuthorizationService = $this->getMockBuilder(AuthorizationService::class)->getMock();
        $this->inject($this->editableViewHelper, 'nodeAuthorizationService', $this->mockNodeAuthorizationService);

        $this->mockTemplateImplementation = $this->getMockBuilder(TemplateImplementation::class)->disableOriginalConstructor()->getMock();

        $this->mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->mockContentContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContentContext));
        $this->mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue(new NodeType('Acme.Test:Headline', [], [])));

        $this->mockTsContext = array('node' => $this->mockNode);
        $this->mockTsRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue($this->mockTsContext));
        $this->mockTemplateImplementation->expects($this->any())->method('getTsRuntime')->will($this->returnValue($this->mockTsRuntime));
        $this->mockView = $this->getAccessibleMock(FluidView::class, array(), array(), '', false);
        $this->mockView->expects($this->any())->method('getTypoScriptObject')->will($this->returnValue($this->mockTemplateImplementation));

        $this->editableViewHelper->initializeArguments();
    }

    /**
     * @param AbstractViewHelper $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper)
    {
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $templateVariables = $this->templateVariables;
        $this->templateVariableContainer->expects($this->any())->method('exists')->will($this->returnCallback(function ($variableName) use ($templateVariables) {
            return isset($templateVariables[$variableName]);
        }));
        $this->templateVariableContainer->expects($this->any())->method('get')->will($this->returnCallback(function ($variableName) use ($templateVariables) {
            return $templateVariables[$variableName];
        }));
    }

    /**
     * Mocks access to the TypoScriptObject
     */
    protected function injectTypoScriptObject()
    {
        $this->viewHelperVariableContainer->expects($this->any())->method('getView')->will($this->returnValue($this->mockView));
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfTheGivenPropertyIsNotAccessible()
    {
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->injectTypoScriptObject();
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfTheTsTemplateObjectIsNotSet()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue',
        );
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     */
    public function renderSetsThePropertyValueAsTagContentIfItExists()
    {
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
    public function renderSetsTheChildNodesAsTagContentIfTheyAreSet()
    {
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
    public function renderDoesNotAddEditingMetaDataAttributesIfInLiveWorkspace()
    {
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
    public function renderDoesNotAddEditingMetaDataAttributesIfUserHasNoAccessToBackend()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue'
        );

        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(false));
        $this->tagBuilder->expects($this->never())->method('addAttribute');

        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->injectTypoScriptObject();
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     */
    public function renderAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasAccessToBackendAndEditNodePrivilegeIsGranted()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue'
        );

        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
        $this->mockNodeAuthorizationService->expects($this->atLeastOnce())->method('isGrantedToEditNode')->will($this->returnValue(true));
        $this->tagBuilder->expects($this->atLeastOnce())->method('addAttribute');

        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->injectTypoScriptObject();
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     */
    public function renderDoesNotAddEditingMetaDataIfEditNodePrivilegeIsNotGranted()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue'
        );

        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
        $this->mockNodeAuthorizationService->expects($this->atLeastOnce())->method('isGrantedToEditNode')->will($this->returnValue(false));
        $this->tagBuilder->expects($this->never())->method('addAttribute');

        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->injectTypoScriptObject();
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     */
    public function renderUsesTheNodeArgumentIfSet()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue'
        );

        $this->tagBuilder->expects($this->once())->method('render');

        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->editableViewHelper->render('someProperty', 'div', $this->mockNode);
    }
}
