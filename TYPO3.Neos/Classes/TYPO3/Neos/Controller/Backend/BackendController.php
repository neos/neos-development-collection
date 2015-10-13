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
use TYPO3\Flow\I18n\Locale;

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
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\XliffService
     */
    protected $xliffService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\UserService
     */
    protected $userService;

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

    /**
     * Returns the cached json array with the xliff labels
     *
     * @return string
     */
    public function getXliffAsJsonAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $locale = new Locale($this->userService->getInterfaceLanguage());

        return $this->xliffService->getCachedJson($locale);
    }
}
