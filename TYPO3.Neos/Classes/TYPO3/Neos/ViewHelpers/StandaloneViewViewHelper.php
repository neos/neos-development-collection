<?php
namespace TYPO3\Neos\ViewHelpers;

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
class StandaloneViewViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @param string $templatePathAndFilename Path and filename of the template to render
     * @param array $arguments Arguments to assign to the template before rendering
     * @return string
     */
    public function render($templatePathAndFilename, $arguments = array())
    {
        $standaloneView = new \Neos\FluidAdaptor\View\StandaloneView($this->controllerContext->getRequest());
        $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
        return $standaloneView->assignMultiple($arguments)->render();
    }
}
