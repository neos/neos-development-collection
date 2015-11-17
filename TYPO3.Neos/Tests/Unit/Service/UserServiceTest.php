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
use TYPO3\Neos\Domain\Service\UserService as UserDomainService;
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
     * @var UserDomainService
     */
    protected $mockUserDomainService;

    /**
     * @var WorkspaceRepository
     */
    protected $mockWorkspaceRepository;

    public function setUp()
    {
        $this->userService = new UserService();

        $this->mockUserDomainService = $this->getMockBuilder(\TYPO3\Neos\Domain\Service\UserService::class)->getMock();
        $this->inject($this->userService, 'userDomainService', $this->mockUserDomainService);

        $this->mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
        $this->inject($this->userService, 'workspaceRepository', $this->mockWorkspaceRepository);
    }

    /**
     * @test
     */
    public function getBackendUserReturnsTheCurrentlyLoggedInUser()
    {
        $mockUser = $this->getMockBuilder('TYPO3\Neos\Domain\Model\User')->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->assertSame($mockUser, $this->userService->getBackendUser());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceReturnsLiveWorkspaceIfNoUserIsLoggedIn()
    {
        $mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue(null));
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
        $this->assertSame($mockLiveWorkspace, $this->userService->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceReturnsTheUsersWorkspaceIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder('TYPO3\Neos\Domain\Model\User')->disableOriginalConstructor()->getMock();
        $mockUserWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getUserName')->with($mockUser)->will($this->returnValue('TheUserName'));
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('user-TheUserName')->will($this->returnValue($mockUserWorkspace));
        $this->assertSame($mockUserWorkspace, $this->userService->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceNameReturnsLiveIfNoUserIsLoggedIn()
    {
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue(null));
        $this->assertSame('live', $this->userService->getCurrentWorkspaceName());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceNameReturnsTheUsersWorkspaceNameIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder('TYPO3\Neos\Domain\Model\User')->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getUserName')->with($mockUser)->will($this->returnValue('TheUserName'));
        $this->assertSame('user-TheUserName', $this->userService->getCurrentWorkspaceName());
    }
}
