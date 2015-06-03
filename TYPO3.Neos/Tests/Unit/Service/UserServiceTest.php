<?php
namespace TYPO3\Neos\Tests\Unit\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * Testcase for the UserService
 *
 */
class UserServiceTest extends UnitTestCase {

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

	public function setUp() {
		$this->userService = new UserService();

		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();
		$this->inject($this->userService, 'securityContext', $this->mockSecurityContext);

		$this->mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->disableOriginalConstructor()->setMethods(array('findOneByName'))->getMock();
		$this->inject($this->userService, 'workspaceRepository', $this->mockWorkspaceRepository);
	}

	/**
	 * @test
	 */
	public function getBackendUserReturnsNullIfSecurityContextHasNotBeenInitialized() {
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('isInitialized')->will($this->returnValue(FALSE));
		$this->mockSecurityContext->expects($this->never())->method('getPartyByType');
		$this->assertNull($this->userService->getBackendUser());
	}

	/**
	 * @test
	 */
	public function getBackendUserReturnsTheCurrentlyLoggedInUserIfSecurityContextIsInitialized() {
		$mockUser = $this->getMockBuilder('TYPO3\Neos\Domain\Model\User')->disableOriginalConstructor()->getMock();
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('isInitialized')->will($this->returnValue(TRUE));
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getPartyByType')->with('TYPO3\Neos\Domain\Model\User')->will($this->returnValue($mockUser));
		$this->assertSame($mockUser, $this->userService->getBackendUser());
	}

	/**
	 * @test
	 */
	public function getCurrentWorkspaceReturnsLiveWorkspaceIfNoUserIsLoggedIn() {
		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue(NULL));
		$this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
		$this->assertSame($mockLiveWorkspace, $this->userService->getCurrentWorkspace());
	}

	/**
	 * @test
	 */
	public function getCurrentWorkspaceReturnsTheUsersWorkspaceIfAUserIsLoggedIn() {
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
	public function getCurrentWorkspaceNameReturnsLiveIfNoUserIsLoggedIn() {
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue(NULL));
		$this->assertSame('live', $this->userService->getCurrentWorkspaceName());
	}

	/**
	 * @test
	 */
	public function getCurrentWorkspaceNameReturnsTheUsersWorkspaceNameIfAUserIsLoggedIn() {
		$mockAccount = $this->getMockBuilder('TYPO3\Flow\Security\Account')->disableOriginalConstructor()->getMock();
		$mockAccount->expects($this->atLeastOnce())->method('getAccountIdentifier')->will($this->returnValue('The UserName'));
		$this->mockSecurityContext->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));
		$this->assertSame('user-TheUserName', $this->userService->getCurrentWorkspaceName());
	}

}