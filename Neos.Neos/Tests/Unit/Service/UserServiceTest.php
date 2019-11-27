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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Service\UserService;
use Neos\Party\Domain\Repository\PartyRepository;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\Flow\Security\AccountRepository;
use Neos\Party\Domain\Service\PartyService;
use Neos\Flow\Security\Account;

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
     * @var UserDomainService
     */
    protected $userDomainService;

    /**
     * @var WorkspaceRepository | \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockWorkspaceRepository;

    /**
     * @var AccountRepository | \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockAccountRepository;

    /**
     * @var PartyService | \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPartyService;

    /**
     * @var PartyRepository | \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPartyRepository;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    public function setUp(): void
    {
        $this->userService = new UserService();
        $this->userDomainService = new UserDomainService();

        $this->mockUserDomainService = $this->getMockBuilder(UserDomainService::class)->getMock();
        $this->inject($this->userService, 'userDomainService', $this->mockUserDomainService);

        $this->mockWorkspaceRepository = $this->getMockBuilder(WorkspaceRepository::class)->disableOriginalConstructor()->setMethods(['findOneByName'])->getMock();
        $this->inject($this->userService, 'workspaceRepository', $this->mockWorkspaceRepository);

        $this->mockAccountRepository = $this->getMockBuilder(AccountRepository::class)->getMock();
        $this->inject($this->userDomainService, 'accountRepository', $this->mockAccountRepository);

        $this->mockPersistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)->getMock();
        $this->inject($this->userDomainService, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockPartyService = $this->getMockBuilder(PartyService::class)->getMock();
        $this->inject($this->userDomainService, 'partyService', $this->mockPartyService);

        $this->mockPartyRepository = $this->getMockBuilder(PartyRepository::class)->getMock();
        $this->inject($this->userDomainService, 'partyRepository', $this->mockPartyRepository);
    }

    /**
     * @test
     */
    public function getBackendUserReturnsTheCurrentlyLoggedInUser()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getCurrentUser')->will(self::returnValue($mockUser));
        self::assertSame($mockUser, $this->userService->getBackendUser());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceReturnsNullIfNoUserIsLoggedIn()
    {
        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getCurrentUser')->will(self::returnValue(null));
        self::assertNull($this->userService->getPersonalWorkspace());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceReturnsTheUsersWorkspaceIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $mockUserWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getCurrentUser')->will(self::returnValue($mockUser));
        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getUserName')->with($mockUser)->will(self::returnValue('TheUserName'));
        $this->mockWorkspaceRepository->expects(self::atLeastOnce())->method('findOneByName')->with('user-TheUserName')->will(self::returnValue($mockUserWorkspace));
        self::assertSame($mockUserWorkspace, $this->userService->getPersonalWorkspace());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceNameReturnsNullIfNoUserIsLoggedIn()
    {
        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getCurrentUser')->will(self::returnValue(null));
        self::assertNull($this->userService->getPersonalWorkspaceName());
    }

    /**
     * @test
     */
    public function getPersonalWorkspaceNameReturnsTheUsersWorkspaceNameIfAUserIsLoggedIn()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();

        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getCurrentUser')->will(self::returnValue($mockUser));
        $this->mockUserDomainService->expects(self::atLeastOnce())->method('getUserName')->with($mockUser)->will(self::returnValue('TheUserName'));
        self::assertSame('user-TheUserName', $this->userService->getPersonalWorkspaceName());
    }

    /**
     * @test
     */
    public function getUserReturnsNullForInvalidUser()
    {
        self::assertNull($this->mockUserDomainService->getUser('NonExistantUser'));
    }

    /**
     * @test
     */
    public function getUsersWillReturnUserOnSecondCall()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $mockAccount = $this->getMockBuilder(Account::class)->disableOriginalConstructor()->getMock();

        $this->setUpGetUser($mockUser);

        $this->mockAccountRepository->expects(self::any())
            ->method('findByAccountIdentifierAndAuthenticationProviderName')
            ->will($this->onConsecutiveCalls(null, $mockAccount));

        $this->userDomainService->getUser('test-user');

        self::assertSame($mockUser, $this->userDomainService->getUser('test-user'));
    }

    /**
     * @test
     */
    public function getUserReturnsUserForValidUser()
    {
        $mockUser = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $mockAccount = $this->getMockBuilder(Account::class)->disableOriginalConstructor()->getMock();

        $this->setUpGetUser($mockUser);

        $this->mockAccountRepository->expects(self::atLeastOnce())
            ->method('findByAccountIdentifierAndAuthenticationProviderName')
            ->willReturn($mockAccount);

        self::assertSame($mockUser, $this->userDomainService->getUser('test-user'));
    }

    protected function setUpGetUser($mockUser)
    {
        $this->mockPartyService->expects(self::atLeastOnce())
            ->method('getAssignedPartyOfAccount')
            ->willReturn($mockUser);

        $this->mockPersistenceManager->expects(self::atLeastOnce())
            ->method('getIdentifierByObject')
            ->with($mockUser)
            ->willReturn('8eb663bd-6886-4b90-a77e-8c3bbc2868f0');

        $this->mockPartyRepository->expects(self::atLeastOnce())
            ->method('findByIdentifier')
            ->willReturn($mockUser);
    }
}
