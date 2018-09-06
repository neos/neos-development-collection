<?php
namespace Neos\Fusion\ViewHelpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\View\FusionView;

/**
 * Render a Fusion object with a relative Fusion path, optionally
 * pushing new variables onto the Fusion context.
 *
 * = Examples =
 *
 * <code title="Simple">
 * Fusion:
 * some.given {
 * 	path = Neos.Fusion:Template
 * 	…
 * }
 * ViewHelper:
 * <ts:render path="some.given.path" />
 * </code>
 * <output>
 * (the evaluated Fusion, depending on the given path)
 * </output>
 *
 * <code title="Fusion from a foreign package">
 * <ts:render path="some.given.path" fusionPackageKey="Acme.Bookstore" />
 * </code>
 * <output>
 * (the evaluated Fusion, depending on the given path)
 * </output>
 */
class RenderViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var FusionView
     */
    protected $fusionView;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('fusionFilePathPattern', 'string', 'Resource pattern to load Fusion from. Defaults to: resource://@package/Private/Fusion/', false);
    }

    /**
     * Evaluate the Fusion object at $path and return the rendered result.
     *
     * @param string $path Relative Fusion path to be rendered
     * @param array $context Additional context variables to be set.
     * @param string $fusionPackageKey The key of the package to load Fusion from, if not from the current context.
     * @return string
     * @throws \Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function render($path, array $context = null, $fusionPackageKey = null)
    {
        if (strpos($path, '/') === 0 || strpos($path, '.') === 0) {
            throw new \InvalidArgumentException('When calling the Fusion render view helper only relative paths are allowed.', 1368740480);
        }
        if (preg_match('/^[a-z0-9.]+$/i', $path) !== 1) {
            throw new \InvalidArgumentException('Invalid path given to the Fusion render view helper ', 1368740484);
        }

        $slashSeparatedPath = str_replace('.', '/', $path);

        if ($fusionPackageKey === null) {
            /** @var $fusionObject AbstractFusionObject */
            $fusionObject = $this->viewHelperVariableContainer->getView()->getFusionObject();
            if ($context !== null) {
                $currentContext = $fusionObject->getRuntime()->getCurrentContext();
                foreach ($context as $key => $value) {
                    $currentContext[$key] = $value;
                }
                $fusionObject->getRuntime()->pushContextArray($currentContext);
            }
            $absolutePath = $fusionObject->getPath() . '/' . $slashSeparatedPath;

            $output = $fusionObject->getRuntime()->render($absolutePath);

            if ($context !== null) {
                $fusionObject->getRuntime()->popContext();
            }
        } else {
            $this->initializeFusionView();
            $this->fusionView->setPackageKey($fusionPackageKey);
            $this->fusionView->setFusionPath($slashSeparatedPath);
            if ($context !== null) {
                $this->fusionView->assignMultiple($context);
            }

            $output = $this->fusionView->render();
        }

        return $output;
    }

    /**
     * Initialize the Fusion View
     *
     * @return void
     */
    protected function initializeFusionView()
    {
        $this->fusionView = new FusionView();
        $this->fusionView->setControllerContext($this->controllerContext);
        $this->fusionView->disableFallbackView();
        if ($this->hasArgument('fusionFilePathPattern')) {
            $this->fusionView->setFusionPathPattern($this->arguments['fusionFilePathPattern']);
        }
    }
}
