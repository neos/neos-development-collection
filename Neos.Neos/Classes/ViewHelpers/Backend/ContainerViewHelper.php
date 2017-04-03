<?php
namespace Neos\Neos\ViewHelpers\Backend;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\Neos\Controller\Backend\MenuHelper;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Exception as NeosException;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Party\Domain\Service\PartyService;

/**
 * ViewHelper for the backend 'container'. Renders the required HTML to integrate
 * the Neos backend into a website.
 */
class ContainerViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var PartyService
     */
    protected $partyService;

    /**
     * @var MenuHelper
     * @Flow\Inject
     */
    protected $menuHelper;

    /**
     * @var PrivilegeManagerInterface
     * @Flow\Inject
     */
    protected $privilegeManager;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param NodeInterface $node
     * @return string
     * @throws NeosException
     */
    public function render(NodeInterface $node)
    {
        if ($this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess') === false) {
            return '';
        }

        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $innerView = new StandaloneView($actionRequest);
        $innerView->setTemplatePathAndFilename('resource://Neos.Neos/Private/Templates/Backend/Content/Container.html');
        $innerView->setFormat('html');
        $innerView->setPartialRootPath('resource://Neos.Neos/Private/Partials');

        $user = $this->partyService->getAssignedPartyOfAccount($this->securityContext->getAccount());

        $innerView->assignMultiple(array(
            'node' => $node,
            'modules' => $this->menuHelper->buildModuleList($this->controllerContext),
            'sites' => $this->menuHelper->buildSiteList($this->controllerContext),
            'user' => $user
        ));

        return $innerView->render();
    }
}
