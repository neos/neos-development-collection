<?php
namespace Neos\Neos\Tests\Unit\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Service\HtmlAugmenter;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\Fusion\Core\Runtime;
use Neos\Neos\Service\ContentElementEditableService;

/**
 * Test for the ContentElementEditableService
 */
class ContentElementEditableServiceTest extends UnitTestCase
{
    /**
     * @var ContentElementEditableService
     */
    protected $contentElementEditableService;

    /**
     * @var PrivilegeManagerInterface
     */
    protected $mockPrivilegeManager;

    /**
     * @var AuthorizationService
     */
    protected $mockNodeAuthorizationService;

    /**
     * @var HtmlAugmenter
     */
    protected $mockHtmlAugmenter;

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
     * @var array
     */
    protected $templateVariables = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->contentElementEditableService = new ContentElementEditableService();

        $this->mockPrivilegeManager = $this->getMockBuilder(PrivilegeManagerInterface::class)->getMock();
        $this->inject($this->contentElementEditableService, 'privilegeManager', $this->mockPrivilegeManager);

        $this->mockNodeAuthorizationService = $this->getMockBuilder(AuthorizationService::class)->getMock();
        $this->inject($this->contentElementEditableService, 'nodeAuthorizationService', $this->mockNodeAuthorizationService);

        $this->mockHtmlAugmenter = $this->getMockBuilder(HtmlAugmenter::class)->getMock();
        $this->inject($this->contentElementEditableService, 'htmlAugmenter', $this->mockHtmlAugmenter);

        $this->mockRuntime = $this->getMockBuilder(\Neos\Fusion\Core\Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockContentContext = $this->getMockBuilder(\Neos\Neos\Domain\Service\ContentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockNode = $this->getMockBuilder(\Neos\ContentRepository\Domain\Model\NodeInterface::class)->getMock();
        $this->mockNode->expects(self::any())->method('getContext')->will(self::returnValue($this->mockContentContext));
        $this->mockNode->expects(self::any())->method('getNodeType')->will(self::returnValue(new NodeType('Acme.Test:Headline', [], [])));

        $this->mockContext = ['node' => $this->mockNode];
        $this->mockRuntime->expects(self::any())->method('getCurrentContext')->will(self::returnValue($this->mockContext));
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfInLiveWorkspace()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->will(self::returnValue('live'));
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfUserHasNoAccessToBackend()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->will(self::returnValue('not-live'));
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will(self::returnValue(false));
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasAccessToBackendAndEditNodePrivilegeIsGranted()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->will(self::returnValue('not-live'));
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will(self::returnValue(true));
        $this->mockNodeAuthorizationService->expects(self::atLeastOnce())->method('isGrantedToEditNode')->will(self::returnValue(true));
        $this->mockHtmlAugmenter->expects(self::atLeastOnce())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataIfEditNodePrivilegeIsNotGranted()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->will(self::returnValue('not-live'));
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will(self::returnValue(true));
        $this->mockNodeAuthorizationService->expects(self::atLeastOnce())->method('isGrantedToEditNode')->will(self::returnValue(false));
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }
}
