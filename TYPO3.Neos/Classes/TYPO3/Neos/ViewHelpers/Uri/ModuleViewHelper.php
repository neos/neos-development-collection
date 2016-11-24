<?php
namespace Neos\Neos\ViewHelpers\Uri;

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
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link rel="some-module" href="{neos:link.module(path: 'system/useradmin')}" />
 * </code>
 *
 * <output>
 * <link rel="some-module" href="neos/system/useradmin" />
 * </output>
 *
 * @Flow\Scope("prototype")
 */
class ModuleViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Render a link to a specific module
     *
     * @param string $path Target module path
     * @param string $action Target module action
     * @param array $arguments Arguments
     * @param string $section The anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html"
     * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @return string The rendered link
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function render($path, $action = null, $arguments = array(), $section = '', $format = '', array $additionalParams = array(), $addQueryString = false, array $argumentsToBeExcludedFromQueryString = array())
    {
        $this->setMainRequestToUriBuilder();
        $modifiedArguments = array('module' => $path);
        if ($arguments !== array()) {
            $modifiedArguments['moduleArguments'] = $arguments;
        }
        if ($action !== null) {
            $modifiedArguments['moduleArguments']['@action'] = $action;
        }

        try {
            return $this->uriBuilder
                ->reset()
                ->setSection($section)
                ->setCreateAbsoluteUri(true)
                ->setArguments($additionalParams)
                ->setAddQueryString($addQueryString)
                ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
                ->setFormat($format)
                ->uriFor('index', $modifiedArguments, 'Backend\Module', 'Neos.Neos');
        } catch (\Neos\Flow\Exception $exception) {
            throw new \Neos\FluidAdaptor\Core\ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Extracted out to this method in order to be better unit-testable.
     *
     * @return void
     */
    protected function setMainRequestToUriBuilder()
    {
        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        $this->uriBuilder->setRequest($mainRequest);
    }
}
