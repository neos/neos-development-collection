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

use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * Testcase for the UserService
 *
 */
class UserServiceTest extends UnitTestCase
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var Context
     */
    protected $mockSecurityContext;

    /**
     * @var WorkspaceRepository
     */
    protected $mockWorkspaceRepository;

    public function setUp()
    {
        $this->userService = new UserService();

        $this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();
        $this->inject($this->userService, 'securityContext', $this->mockSecurityContext);

        $this->mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
        $this->inject($this->userService, 'workspaceRepository', $this->mockWorkspaceRepository);
    }

    /**
     * @test
     */
    public function getBackendUserReturnsNullIfSecurityContextHasNotBeenInitialized()
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('canBeInitialized')->will($this->returnValue(false));
        $this->mockSecurityContext->expects($this->never())->method('getPartyByType');
        $this->assertNull($this->userService->getBackendUser());
    }

    /**
     * @test
     */
    public function getBackendUserReturnsTheCurrentlyLoggedInUserIfSecurityContextIsInitialized()
    {
        $mockUser = $this->getMockBuilder('TYPO3\Neos\Domain\Model\User')->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('canBeInitialized')->will($this->returnValue(true));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getPartyByType')->with('TYPO3\Neos\Domain\Model\User')->will($this->returnValue($mockUser));
        $this->assertSame($mockUser, $this->userService->getBackendUser());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceReturnsLiveWorkspaceIfNoUserIsLoggedIn()
    {
        $mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue(null));
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
        $this->assertSame($mockLiveWorkspace, $this->userService->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceReturnsTheUsersWorkspaceIfAUserIsLoggedIn()
    {
        $mockUserWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $mockAccount = $this->getMockBuilder('TYPO3\Flow\Security\Account')->disableOriginalConstructor()->getMock();
        $mockAccount->expects($this->atLeastOnce())->method('getAccountIdentifier')->will($this->returnValue('The UserName'));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('user-TheUserName')->will($this->returnValue($mockUserWorkspace));
        $this->assertSame($mockUserWorkspace, $this->userService->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceNameReturnsLiveIfNoUserIsLoggedIn()
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue(null));
        $this->assertSame('live', $this->userService->getCurrentWorkspaceName());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceNameReturnsTheUsersWorkspaceNameIfAUserIsLoggedIn()
    {
        $mockAccount = $this->getMockBuilder('TYPO3\Flow\Security\Account')->disableOriginalConstructor()->getMock();
        $mockAccount->expects($this->atLeastOnce())->method('getAccountIdentifier')->will($this->returnValue('The UserName'));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));
        $this->assertSame('user-TheUserName', $this->userService->getCurrentWorkspaceName());
    }
}
