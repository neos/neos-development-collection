<?php
namespace Neos\Neos\ViewHelpers\Link;

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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <neos:link.module path="system/useradmin">some link</neos:link.module>
 * </code>
 * <output>
 * <a href="neos/system/useradmin">some link</a>
 * </output>
 */
class ModuleViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @Flow\Inject
     * @var \Neos\Neos\ViewHelpers\Uri\ModuleViewHelper
     */
    protected $uriModuleViewHelper;

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

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
        $this->uriModuleViewHelper->setRenderingContext($this->renderingContext);

        $uri = $this->uriModuleViewHelper->render($path, $action, $arguments, $section, $format, $additionalParams, $addQueryString, $argumentsToBeExcludedFromQueryString);
        if ($uri !== null) {
            $this->tag->addAttribute('href', $uri);
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
