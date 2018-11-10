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
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Neos\Controller\BackendUserTranslationTrait;

/**
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
     * @return \Neos\Error\Messages\Message returns false
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @see redirect()
     * @api
     * @todo move it to somewhere else
     */
    protected function redirectWithParentRequest($actionName, $controllerName = null, $packageKey = null, array $arguments = null, $delay = 0, $statusCode = 303, $format = null)
    {
        $request = $this->getControllerContext()->getRequest()->getMainRequest();
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        if ($packageKey !== null && strpos($packageKey, '\\') !== false) {
            list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
        } else {
            $subpackageKey = null;
        }
        if ($format === null) {
            $uriBuilder->setFormat($this->request->getFormat());
        } else {
            $uriBuilder->setFormat($format);
        }

        $uri = $uriBuilder->setCreateAbsoluteUri(true)->uriFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
        $this->redirectToUri($uri, $delay, $statusCode);
    }
}
