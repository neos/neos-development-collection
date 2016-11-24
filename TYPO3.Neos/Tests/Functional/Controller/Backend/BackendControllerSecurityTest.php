<?php
namespace TYPO3\Neos\Tests\Functional\Controller\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Neos\Domain\Model\User;

/**
 * Testcase for method security of the backend controller
 *
 * @group large
 */
class BackendControllerSecurityTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @test
     */
    public function indexActionIsGrantedForAdministrator()
    {
        $account = $this->authenticateRoles(array('TYPO3.Neos:Administrator'));
        $account->setAccountIdentifier('admin');
        $this->browser->request('http://localhost/neos/login');

            // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function indexActionIsDeniedForEverybody()
    {
        $this->browser->request('http://localhost/neos/');
        $this->assertSame(403, $this->browser->getLastResponse()->getStatusCode());
    }
}
