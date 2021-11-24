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
use Neos\Neos\Controller\Backend\BackendController;
use Neos\Neos\Service\BackendRedirectionService;

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
        $backendRedirectionServiceMock = $this->getMockBuilder(BackendRedirectionService::class)->getMock();
        $backendRedirectionServiceMock
            ->expects(self::atLeastOnce())
            ->method('getAfterLoginRedirectionUri')
            ->willReturn('http://localhost/');

        $backendController = $this->objectManager->get(BackendController::class);
        $this->inject($backendController, 'backendRedirectionService', $backendRedirectionServiceMock);

        $account = $this->authenticateRoles(['Neos.Neos:Administrator']);
        $account->setAccountIdentifier('admin');
        $this->browser->request('http://localhost/neos/login');
    }

    /**
     * @test
     */
    public function indexActionIsDeniedForEverybody()
    {
        $this->browser->request('http://localhost/neos/');
        self::assertSame(403, $this->browser->getLastResponse()->getStatusCode());
    }
}
