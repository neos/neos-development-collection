<?php
namespace TYPO3\Neos\Tests\Unit\Command;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cli\ConsoleOutput;

/**
 * Testcase for the "UserCommandController"
 *
 */
class UserCommandControllerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConsoleOutput;

    public function setUp()
    {
        parent::setUp();
        $this->mockConsoleOutput = $this->getMockBuilder('TYPO3\Flow\Cli\ConsoleOutput')->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function createCommandCreatesAndAddsUser()
    {
        $mockUserFactory = $this->getMock('TYPO3\Neos\Domain\Factory\UserFactory');
        $mockPartyRepository = $this->getMock('TYPO3\Party\Domain\Repository\PartyRepository');
        $mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository');
        $mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
        $mockUser = $this->getMock('TYPO3\Neos\Domain\Model\User');
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
        $mockUser->expects($this->any())->method('getAccounts')->will($this->returnValue(array($mockAccount)));

        $controller = new \TYPO3\Neos\Command\UserCommandController();
        $this->inject($controller, 'output', $this->mockConsoleOutput);
        $this->inject($controller, 'response', $mockResponse);
        $this->inject($controller, 'userFactory', $mockUserFactory);
        $this->inject($controller, 'partyRepository', $mockPartyRepository);
        $this->inject($controller, 'accountRepository', $mockAccountRepository);

        $mockUserFactory->expects($this->once())->method('create')->with('username', 'password', 'John', 'Doe', array('TYPO3.Neos:Editor'))->will($this->returnValue($mockUser));
        $mockPartyRepository->expects($this->once())->method('add')->with($mockUser);
        $mockAccountRepository->expects($this->once())->method('add')->with($mockAccount);

        $controller->createCommand('username', 'password', 'John', 'Doe');
    }
}
