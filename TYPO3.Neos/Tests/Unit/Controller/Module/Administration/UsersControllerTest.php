<?php
namespace TYPO3\Neos\Tests\Unit\Controller\Module\Administration;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the "UsersController"
 *
 */
class UsersControllerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function createActionCreatesAndAddsUserWithTheGivenRole()
    {
        $mockUserFactory = $this->getMock('TYPO3\Neos\Domain\Factory\UserFactory');
        $mockPartyRepository = $this->getMock('TYPO3\Party\Domain\Repository\PartyRepository');
        $mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
        $mockResponse = $this->getMock('TYPO3\Flow\Http\Response');
        $mockUser = $this->getMock('TYPO3\Neos\Domain\Model\User');
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
        $mockUser->expects($this->any())->method('getAccounts')->will($this->returnValue(array($mockAccount)));

        $controller = $this->getAccessibleMock('TYPO3\Neos\Controller\Module\Administration\UsersController', array('redirect'));
        $this->inject($controller, 'response', $mockResponse);
        $this->inject($controller, 'userFactory', $mockUserFactory);
        $this->inject($controller, 'partyRepository', $mockPartyRepository);
        $this->inject($controller, 'accountRepository', $mockAccountRepository);

        $mockUserFactory->expects($this->once())->method('create')->with('username', 'password', 'John', 'Doe', array('TYPO3.Neos:Administrator'))->will($this->returnValue($mockUser));
        $mockPartyRepository->expects($this->once())->method('add')->with($mockUser);
        $mockAccountRepository->expects($this->once())->method('add')->with($mockAccount);

        $controller->createAction('username', array('password'), 'John', 'Doe', 'TYPO3.Neos:Administrator');
    }
}
