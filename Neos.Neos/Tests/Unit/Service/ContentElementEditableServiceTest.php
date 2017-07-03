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
use Neos\Neos\Service\HtmlAugmenter;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\Domain\Model\NodeType;
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
    protected $templateVariables = array();

    public function setUp()
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
        $this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContentContext));
        $this->mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue(new NodeType('Acme.Test:Headline', [], [])));

        $this->mockContext = array('node' => $this->mockNode);
        $this->mockRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue($this->mockContext));
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfInLiveWorkspace()
    {
        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('live'));
        $this->mockHtmlAugmenter->expects($this->never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfUserHasNoAccessToBackend()
    {
        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will($this->returnValue(false));
        $this->mockHtmlAugmenter->expects($this->never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasAccessToBackendAndEditNodePrivilegeIsGranted()
    {
        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
        $this->mockNodeAuthorizationService->expects($this->atLeastOnce())->method('isGrantedToEditNode')->will($this->returnValue(true));
        $this->mockHtmlAugmenter->expects($this->atLeastOnce())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyDoesNotAddEditingMetaDataIfEditNodePrivilegeIsNotGranted()
    {
        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
        $this->mockNodeAuthorizationService->expects($this->atLeastOnce())->method('isGrantedToEditNode')->will($this->returnValue(false));
        $this->mockHtmlAugmenter->expects($this->never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }
}
