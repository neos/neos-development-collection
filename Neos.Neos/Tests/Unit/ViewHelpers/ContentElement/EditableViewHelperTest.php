<?php
namespace Neos\Neos\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\ViewHelpers\ContentElement\EditableViewHelper;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\Helpers\FluidView;
use Neos\Fusion\FusionObjects\TemplateImplementation;
use Neos\Neos\Service\ContentElementEditableService;

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
     * @var ContentElementEditableService
     */
    protected $mockContentElementEditableService;

    /**
     * @var Runtime
     */
    protected $mockRuntime;

    /**
     * @var array
     */
    protected $mockContext;

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

        $this->mockContentElementEditableService = $this->getMockBuilder(ContentElementEditableService::class)->getMock();
        $this->inject($this->editableViewHelper, 'contentElementEditableService', $this->mockContentElementEditableService);

        $this->mockTemplateImplementation = $this->getMockBuilder(TemplateImplementation::class)->disableOriginalConstructor()->getMock();

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $this->mockContentContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContentContext));
        $this->mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue(new NodeType('Acme.Test:Headline', [], [])));

        $this->mockContext = array('node' => $this->mockNode);
        $this->mockRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue($this->mockContext));
        $this->mockTemplateImplementation->expects($this->any())->method('getRuntime')->will($this->returnValue($this->mockRuntime));
        $this->mockView = $this->getAccessibleMock(FluidView::class, array(), array(), '', false);
        $this->mockView->expects($this->any())->method('getFusionObject')->will($this->returnValue($this->mockTemplateImplementation));

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
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfTheGivenPropertyIsNotAccessible()
    {
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->injectTypoScriptObject();
        $this->editableViewHelper->render('someProperty');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
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
    public function renderCallsContentElementEditableServiceForAugmentation()
    {
        $this->templateVariables = array(
            'someProperty' => 'somePropertyValue'
        );
        $this->tagBuilder->expects($this->once())->method('render')->with()->willReturn('<div>somePropertyValue</div>');
        $this->mockContentElementEditableService->expects($this->once())->method('wrapContentProperty')->with($this->mockNode, 'someProperty', '<div>somePropertyValue</div>');
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
