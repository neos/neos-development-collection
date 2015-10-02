<?php
namespace TYPO3\Neos\Tests\Unit\Domain\Factory;

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
 * Testcase for the UserFactory
 *
 */
class UserFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function createSetsPersonName()
    {
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
        $mockAccountFactory = $this->getMock('TYPO3\Flow\Security\AccountFactory');
        $mockAccountFactory->expects($this->any())->method('createAccountWithPassword')->will($this->returnValue($mockAccount));

        $factory = new \TYPO3\Neos\Domain\Factory\UserFactory();
        $this->inject($factory, 'accountFactory', $mockAccountFactory);

        $user = $factory->create('username', 'password', 'John', 'Doe');

        $this->assertEquals('John Doe', $user->getName()->getFullName());
    }

    /**
     * @test
     */
    public function createAlsoCreatesAccount()
    {
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
        $mockAccountFactory = $this->getMock('TYPO3\Flow\Security\AccountFactory');

        $factory = new \TYPO3\Neos\Domain\Factory\UserFactory();
        $this->inject($factory, 'accountFactory', $mockAccountFactory);

        $mockAccountFactory->expects($this->atLeastOnce())->method('createAccountWithPassword')->with('username', 'password', array('TYPO3.Neos:Editor'), 'Typo3BackendProvider')->will($this->returnValue($mockAccount));

        $user = $factory->create('username', 'password', 'John', 'Doe');
    }
}
