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

namespace Neos\Neos\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\View\StandaloneView;

/**
 * A View Helper to render a fluid template based on the given template path and filename.
 *
 * This will just set up a standalone Fluid view and render the template found at the
 * given path and filename. Any arguments passed will be assigned to that template,
 * the rendering result is returned.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <neos:standaloneView templatePathAndFilename="fancyTemplatePathAndFilename" arguments="{foo: bar, quux: baz}" />
 * </code>
 * <output>
 * <some><fancy/></html
 * (depending on template and arguments given)
 * </output>
 *
 * @Flow\Scope("prototype")
 */
class StandaloneViewViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(
            'templatePathAndFilename',
            'string',
            'Path and filename of the template to render',
            true
        );
        $this->registerArgument(
            'arguments',
            'array',
            'Arguments to assign to the template before rendering',
            false,
            []
        );
    }

    /**
     * @return string
     * @throws \Neos\FluidAdaptor\Exception
     */
    public function render(): string
    {
        $standaloneView = new StandaloneView($this->controllerContext->getRequest());
        $standaloneView->setTemplatePathAndFilename($this->arguments['templatePathAndFilename']);
        return $standaloneView->assignMultiple($this->arguments['arguments'])->render();
    }
}
