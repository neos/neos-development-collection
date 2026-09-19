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

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Service\HtmlAugmenter;
use Neos\Neos\Service\ContentElementEditableService;
use PHPUnit\Framework\Attributes\Test;

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
     * @var Node
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
        $this->markTestSkipped('Re-enable with Neos 9.0');
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

        $this->mockNode = $this->getMockBuilder(Node::class)->getMock();
        $this->mockNode->expects(self::any())->method('getContext')->willReturn($this->mockContentContext);
        $this->mockNode->expects(self::any())->method('getNodeType')->willReturn(new NodeType('Acme.Test:Headline', [], []));

        $this->mockContext = ['node' => $this->mockNode];
        $this->mockRuntime->expects(self::any())->method('getCurrentContext')->willReturn($this->mockContext);
    }

    #[Test]
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfInLiveWorkspace()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->willReturn('live');
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    #[Test]
    public function wrapContentPropertyDoesNotAddEditingMetaDataAttributesIfUserHasNoAccessToBackend()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->willReturn('not-live');
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->willReturn(false);
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    #[Test]
    public function wrapContentPropertyAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasAccessToBackendAndEditNodePrivilegeIsGranted()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->willReturn('not-live');
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->willReturn(true);
        $this->mockNodeAuthorizationService->expects(self::atLeastOnce())->method('isGrantedToEditNode')->willReturn(true);
        $this->mockHtmlAugmenter->expects(self::atLeastOnce())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    #[Test]
    public function wrapContentPropertyDoesNotAddEditingMetaDataIfEditNodePrivilegeIsNotGranted()
    {
        $this->mockContentContext->expects(self::atLeastOnce())->method('getWorkspaceName')->willReturn('not-live');
        $this->mockPrivilegeManager->expects(self::atLeastOnce())->method('isPrivilegeTargetGranted')->with('Neos.Neos:Backend.GeneralAccess')->willReturn(true);
        $this->mockNodeAuthorizationService->expects(self::atLeastOnce())->method('isGrantedToEditNode')->willReturn(false);
        $this->mockHtmlAugmenter->expects(self::never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }
}
