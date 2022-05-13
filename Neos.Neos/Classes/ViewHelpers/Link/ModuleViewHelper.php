<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\Link;

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
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute(
            'rel',
            'string',
            'Specifies the relationship between the current document and the linked document'
        );
        $this->registerTagAttribute(
            'rev',
            'string',
            'Specifies the relationship between the linked document and the current document'
        );
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');

        $this->registerArgument('path', 'string', 'Target module path', true);
        $this->registerArgument('action', 'string', 'Target module action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html"');
        $this->registerArgument(
            'additionalParams',
            'array',
            'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)',
            false,
            []
        );
        $this->registerArgument(
            'addQueryString',
            'boolean',
            'If set, the current query parameters will be kept in the URI',
            false,
            false
        );
        $this->registerArgument(
            'argumentsToBeExcludedFromQueryString',
            'array',
            'arguments to be removed from the URI. Only active if $addQueryString = true',
            false,
            []
        );
    }

    /**
     * Render a link to a specific module
     *
     * @return string The rendered link
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function render(): string
    {
        $this->uriModuleViewHelper->setRenderingContext($this->renderingContext);

        $this->uriModuleViewHelper->setArguments([
            'path' => $this->arguments['path'],
            'action' => $this->arguments['action'],
            'arguments' => $this->arguments['arguments'],
            'section' => $this->arguments['section'],
            'format' => $this->arguments['format'],
            'additionalParams' => $this->arguments['additionalParams'],
            'addQueryString' => $this->arguments['addQueryString'],
            'argumentsToBeExcludedFromQueryString' => $this->arguments['argumentsToBeExcludedFromQueryString']
        ]);
        $uri = $this->uriModuleViewHelper->render();
        if ($uri !== null) {
            $this->tag->addAttribute('href', $uri);
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
