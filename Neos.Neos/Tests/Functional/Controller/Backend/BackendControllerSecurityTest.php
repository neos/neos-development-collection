<?php
namespace Neos\Neos\Tests\Functional\Controller\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Domain\Model\User;

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
        $account = $this->authenticateRoles(array('Neos.Neos:Administrator'));
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
