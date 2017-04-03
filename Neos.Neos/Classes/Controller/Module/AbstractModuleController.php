<?php
namespace Neos\Neos\Controller\Module;

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
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Neos\Controller\BackendUserTranslationTrait;

/**
 * The TYPO3 Abstract module controller
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractModuleController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @var array
     */
    protected $moduleConfiguration;

    /**
     * @return void
     */
    protected function initializeAction()
    {
        $this->moduleConfiguration = $this->request->getInternalArgument('__moduleConfiguration');
    }

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        $view->assign('moduleConfiguration', $this->moduleConfiguration);
    }

    /**
     * Use this method to set an alternative title than the module label
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->request->setArgument('title', $title);
    }

    /**
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * Display no flash message at all on errors.
     *
     * @return \Neos\Error\Messages\Message returns FALSE
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }
}
