<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\View\StandaloneView;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
     * @var \TYPO3\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * @var \TYPO3\Neos\Controller\Backend\MenuHelper
     * @Flow\Inject
     */
    protected $menuHelper;

    /**
     * @var \TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface
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
        if ($this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess') === false) {
            return '';
        }

        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $innerView = new StandaloneView($actionRequest);
        $innerView->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Backend/Content/Container.html');
        $innerView->setFormat('html');
        $innerView->setPartialRootPath('resource://TYPO3.Neos/Private/Partials');

        $user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

        $sites = $this->menuHelper->buildSiteList($this->controllerContext);

        $innerView->assignMultiple(array(
            'node' => $node,
            'modules' => $this->settings['modules'],
            'sites' => $sites,
            'user' => $user
        ));

        return $innerView->render();
    }
}
