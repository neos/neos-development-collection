<?php
namespace TYPO3\Neos\Tests\Unit\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Service\HtmlAugmenter;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Service\AuthorizationService;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\Neos\Service\ContentElementEditableService;

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

        $this->mockTsRuntime = $this->getMockBuilder(\TYPO3\TypoScript\Core\Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockContentContext = $this->getMockBuilder(\TYPO3\Neos\Domain\Service\ContentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockNode = $this->getMockBuilder(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class)->getMock();
        $this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContentContext));
        $this->mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue(new NodeType('Acme.Test:Headline', [], [])));

        $this->mockTsContext = array('node' => $this->mockNode);
        $this->mockTsRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue($this->mockTsContext));
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
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(false));
        $this->mockHtmlAugmenter->expects($this->never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }

    /**
     * @test
     */
    public function wrapContentPropertyAddsEditingMetaDataAttributesIfInUserWorkspaceAndUserHasAccessToBackendAndEditNodePrivilegeIsGranted()
    {
        $this->mockContentContext->expects($this->atLeastOnce())->method('getWorkspaceName')->will($this->returnValue('not-live'));
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
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
        $this->mockPrivilegeManager->expects($this->atLeastOnce())->method('isPrivilegeTargetGranted')->with('TYPO3.Neos:Backend.GeneralAccess')->will($this->returnValue(true));
        $this->mockNodeAuthorizationService->expects($this->atLeastOnce())->method('isGrantedToEditNode')->will($this->returnValue(false));
        $this->mockHtmlAugmenter->expects($this->never())->method('addAttributes');
        $this->contentElementEditableService->wrapContentProperty($this->mockNode, 'someProperty', '<div>someRenderedPropertyValue</div>');
    }
}
