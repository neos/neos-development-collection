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

namespace Neos\Neos\ViewHelpers\Uri;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for creating links to modules.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link rel="some-module" href="{neos:uri.module(path: 'system/useradmin')}" />
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
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('path', 'string', 'Target module path', true);
        $this->registerArgument('action', 'string', 'Target module action');
        $this->registerArgument('arguments', 'string', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html"', false, '');
        $this->registerArgument(
            'additionalParams',
            'string',
            'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)',
            false,
            []
        );
        $this->registerArgument(
            'addQueryString',
            'string',
            'If set, the current query parameters will be kept in the URI',
            false,
            false
        );
        $this->registerArgument(
            'argumentsToBeExcludedFromQueryString',
            'string',
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
        $this->setMainRequestToUriBuilder();
        $modifiedArguments = ['module' => $this->arguments['path']];
        if ($this->arguments['arguments'] !== []) {
            $modifiedArguments['moduleArguments'] = $this->arguments['arguments'];
        }
        if ($this->arguments['action'] !== null) {
            $modifiedArguments['moduleArguments']['@action'] = $this->arguments['action'];
        }

        try {
            return $this->uriBuilder
                ->reset()
                ->setSection($this->arguments['section'])
                ->setCreateAbsoluteUri(true)
                ->setArguments($this->arguments['additionalParams'])
                ->setAddQueryString($this->arguments['addQueryString'])
                ->setArgumentsToBeExcludedFromQueryString($this->arguments['argumentsToBeExcludedFromQueryString'])
                ->setFormat($this->arguments['format'])
                ->uriFor('index', $modifiedArguments, 'Backend\Module', 'Neos.Neos');
        } catch (\Neos\Flow\Exception $exception) {
            throw new \Neos\FluidAdaptor\Core\ViewHelper\Exception(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Extracted out to this method in order to be better unit-testable.
     *
     * @return void
     */
    protected function setMainRequestToUriBuilder(): void
    {
        /** @var ActionRequest $mainRequest */
        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        $this->uriBuilder->setRequest($mainRequest);
    }
}
