<?php
namespace TYPO3\Neos\Controller\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The TYPO3 Backend controller
 *
 * @Flow\Scope("singleton")
 */
class BackendController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\BackendRedirectionService
     */
    protected $backendRedirectionService;

    /**
     * Default action of the backend controller.
     *
     * @return void
     */
    public function indexAction()
    {
        $redirectionUri = $this->backendRedirectionService->getAfterLoginRedirectionUri($this->request);
        if ($redirectionUri === null) {
            $redirectionUri = $this->uriBuilder->uriFor('index', array(), 'Login', 'TYPO3.Neos');
        }
        $this->redirectToUri($redirectionUri);
    }
}
