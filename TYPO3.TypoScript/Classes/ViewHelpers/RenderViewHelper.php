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
use Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject;
use Neos\Fusion\View\TypoScriptView;

/**
 * Render a TypoScript object with a relative TypoScript path, optionally
 * pushing new variables onto the TypoScript context.
 *
 * = Examples =
 *
 * <code title="Simple">
 * TypoScript:
 * some.given {
 * 	path = Neos.Fusion:Template
 * 	…
 * }
 * ViewHelper:
 * <ts:render path="some.given.path" />
 * </code>
 * <output>
 * (the evaluated TypoScript, depending on the given path)
 * </output>
 *
 * <code title="TypoScript from a foreign package">
 * <ts:render path="some.given.path" typoScriptPackageKey="Acme.Bookstore" />
 * </code>
 * <output>
 * (the evaluated TypoScript, depending on the given path)
 * </output>
 */
class RenderViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var TypoScriptView
     */
    protected $typoScriptView;

    /**
     * Initialize the arguments.
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('typoScriptFilePathPattern', 'string', 'Resource pattern to load TypoScript from. Defaults to: resource://@package/Private/TypoScript/', false);
    }

    /**
     * Evaluate the TypoScript object at $path and return the rendered result.
     *
     * @param string $path Relative TypoScript path to be rendered
     * @param array $context Additional context variables to be set.
     * @param string $typoScriptPackageKey The key of the package to load TypoScript from, if not from the current context.
     * @return string
     * @throws \InvalidArgumentException
     */
    public function render($path, array $context = null, $typoScriptPackageKey = null)
    {
        if (strpos($path, '/') === 0 || strpos($path, '.') === 0) {
            throw new \InvalidArgumentException('When calling the TypoScript render view helper only relative paths are allowed.', 1368740480);
        }
        if (preg_match('/^[a-z0-9.]+$/i', $path) !== 1) {
            throw new \InvalidArgumentException('Invalid path given to the TypoScript render view helper ', 1368740484);
        }

        $slashSeparatedPath = str_replace('.', '/', $path);

        if ($typoScriptPackageKey === null) {
            /** @var $typoScriptObject AbstractTypoScriptObject */
            $typoScriptObject = $this->viewHelperVariableContainer->getView()->getTypoScriptObject();
            if ($context !== null) {
                $currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();
                foreach ($context as $key => $value) {
                    $currentContext[$key] = $value;
                }
                $typoScriptObject->getTsRuntime()->pushContextArray($currentContext);
            }
            $absolutePath = $typoScriptObject->getPath() . '/' . $slashSeparatedPath;

            $output = $typoScriptObject->getTsRuntime()->render($absolutePath);

            if ($context !== null) {
                $typoScriptObject->getTsRuntime()->popContext();
            }
        } else {
            $this->initializeTypoScriptView();
            $this->typoScriptView->setPackageKey($typoScriptPackageKey);
            $this->typoScriptView->setTypoScriptPath($slashSeparatedPath);
            if ($context !== null) {
                $this->typoScriptView->assignMultiple($context);
            }

            $output = $this->typoScriptView->render();
        }

        return $output;
    }

    /**
     * Initialize the TypoScript View
     *
     * @return void
     */
    protected function initializeTypoScriptView()
    {
        $this->typoScriptView = new TypoScriptView();
        $this->typoScriptView->setControllerContext($this->controllerContext);
        $this->typoScriptView->disableFallbackView();
        if ($this->hasArgument('typoScriptFilePathPattern')) {
            $this->typoScriptView->setTypoScriptPathPattern($this->arguments['typoScriptFilePathPattern']);
        }
    }
}
