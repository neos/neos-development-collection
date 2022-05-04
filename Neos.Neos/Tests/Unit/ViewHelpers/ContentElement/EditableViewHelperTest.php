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

use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\ViewHelpers\ContentElement\EditableViewHelper;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
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
    protected $templateVariables = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->editableViewHelper = $this->getAccessibleMock(EditableViewHelper::class, ['renderChildren']);

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
        $this->mockNode->expects(self::any())->method('getContext')->willReturn($this->mockContentContext);
        $this->mockNode->expects(self::any())->method('getNodeType')->willReturn(new NodeType('Acme.Test:Headline', [], []));

        $this->mockContext = ['node' => $this->mockNode];
        $this->mockRuntime->expects(self::any())->method('getCurrentContext')->willReturn($this->mockContext);
        $this->mockTemplateImplementation->expects(self::any())->method('getRuntime')->willReturn($this->mockRuntime);
        $this->mockView = $this->getAccessibleMock(FluidView::class, [], [], '', false);
        $this->mockView->expects(self::any())->method('getFusionObject')->willReturn($this->mockTemplateImplementation);
    }

    /**
     * @param AbstractViewHelper $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper)
    {
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $templateVariables = $this->templateVariables;
        $this->templateVariableContainer->expects(self::any())->method('exists')->willReturnCallback(static function ($variableName) use ($templateVariables) {
            return isset($templateVariables[$variableName]);
        });
        $this->templateVariableContainer->expects(self::any())->method('get')->willReturnCallback(static function ($variableName) use ($templateVariables) {
            return $templateVariables[$variableName];
        });
    }

    /**
     * Mocks access to the FusionObject
     */
    protected function setUpViewMockAccess()
    {
        $this->viewHelperVariableContainer->expects(self::any())->method('getView')->willReturn($this->mockView);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfTheGivenPropertyIsNotAccessible(): void
    {
        $this->expectException(Exception::class);
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->setUpViewMockAccess();
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper);
        $this->editableViewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfTheTsTemplateObjectIsNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->templateVariables = [
            'someProperty' => 'somePropertyValue',
        ];
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper);
        $this->editableViewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsThePropertyValueAsTagContentIfItExists(): void
    {
        $this->mockContentElementEditableService->expects(self::once())->method('wrapContentProperty')->willReturn('someWrappedContent');
        $this->templateVariables = [
            'someProperty' => 'somePropertyValue'
        ];
        $this->tagBuilder->expects(self::once())->method('setContent')->with('somePropertyValue');
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->setUpViewMockAccess();
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper, ['property' => 'someProperty']);
        $this->editableViewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsTheChildNodesAsTagContentIfTheyAreSet(): void
    {
        $this->mockContentElementEditableService->expects(self::once())->method('wrapContentProperty')->willReturn('someWrappedContent');
        $this->templateVariables = [
            'someProperty' => 'somePropertyValue'
        ];

        $this->editableViewHelper->expects(self::atLeastOnce())->method('renderChildren')->willReturn('overriddenPropertyValue');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('overriddenPropertyValue');
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->setUpViewMockAccess();
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper, ['property' => 'someProperty']);
        $this->editableViewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsContentElementEditableServiceForAugmentation(): void
    {
        $this->templateVariables = [
            'someProperty' => 'somePropertyValue'
        ];
        $this->tagBuilder->expects(self::once())->method('render')->with()->willReturn('<div>somePropertyValue</div>');
        $this->mockContentElementEditableService->expects(self::once())->method('wrapContentProperty')->with($this->mockNode, 'someProperty', '<div>somePropertyValue</div>')->willReturn('someWrappedContent');
        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->setUpViewMockAccess();
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper, ['property' => 'someProperty']);
        $this->editableViewHelper->render();
    }

    /**
     * @test
     */
    public function renderUsesTheNodeArgumentIfSet(): void
    {
        $this->mockContentElementEditableService->expects(self::once())->method('wrapContentProperty')->willReturn('someWrappedContent');
        $this->templateVariables = [
            'someProperty' => 'somePropertyValue'
        ];

        $this->tagBuilder->expects(self::once())->method('render');

        $this->injectDependenciesIntoViewHelper($this->editableViewHelper);
        $this->editableViewHelper = $this->prepareArguments($this->editableViewHelper, ['property' => 'someProperty', 'node' => $this->mockNode]);
        $this->editableViewHelper->render();
    }
}
