<?php
namespace Neos\Neos\Controller\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Controller\Backend\MenuHelper;
use Neos\Neos\Controller\BackendUserTranslationTrait;

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
            ->uriFor('index', array(), 'Backend\Backend', 'Neos.Neos');
        return json_encode(array(
            'contentModuleUri' => $contentModuleUri,
            'sites' => $this->menuHelper->buildSiteList($this->controllerContext),
            'modules' => $this->menuHelper->buildModuleList($this->controllerContext)
        ));
    }
}
