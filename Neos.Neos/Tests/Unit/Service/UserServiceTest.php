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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService as UserDomainService;
use Neos\Neos\Service\UserService;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;

/**
 * Test case for the UserService
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

        $this->mockUserDomainService = $this->getMockBuilder(UserDomainService::class)->getMock();
        $this->inject($this->userService, 'userDomainService', $this->mockUserDomainService);

        $this->mockWorkspaceRepository = $this->getMockBuilder(WorkspaceRepository::class)->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
        $this->inject($this->userService, 'workspaceRepository', $this->mockWorkspaceRepository);
    }

    /**
     * @test
     */
    public function getBackendUserReturnsTheCurrentlyLoggedInUser()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->assertSame($mockUser, $this->userService->getBackendUser());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceReturnsNullIfNoUserIsLoggedIn()
    {
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue(null));
        $this->assertNull($this->userService->getPersonalWorkspace());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceReturnsTheUsersWorkspaceIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $mockUserWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getUserName')->with($mockUser)->will($this->returnValue('TheUserName'));
        $this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('user-TheUserName')->will($this->returnValue($mockUserWorkspace));
        $this->assertSame($mockUserWorkspace, $this->userService->getPersonalWorkspace());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceNameReturnsNullIfNoUserIsLoggedIn()
    {
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue(null));
        $this->assertNull($this->userService->getPersonalWorkspaceName());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceNameReturnsTheUsersWorkspaceNameIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getCurrentUser')->will($this->returnValue($mockUser));
        $this->mockUserDomainService->expects($this->atLeastOnce())->method('getUserName')->with($mockUser)->will($this->returnValue('TheUserName'));
        $this->assertSame('user-TheUserName', $this->userService->getPersonalWorkspaceName());
    }
}
