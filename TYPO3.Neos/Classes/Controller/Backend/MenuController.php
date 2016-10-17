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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Neos\Controller\Backend\MenuHelper;
use TYPO3\Neos\Controller\BackendUserTranslationTrait;

/**
 * @Flow\Scope("singleton")
 */
class MenuController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @var MenuHelper
     * @Flow\Inject
     */
    protected $menuHelper;

    /**
     * @return string
     */
    public function indexAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');

        $contentModuleUri = $this->getControllerContext()->getUriBuilder()
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
        return json_encode(array(
            'contentModuleUri' => $contentModuleUri,
            'sites' => $this->menuHelper->buildSiteList($this->controllerContext),
            'modules' => $this->menuHelper->buildModuleList($this->controllerContext)
        ));
    }
}
