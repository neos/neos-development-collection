<?php
namespace Neos\Fusion\View;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;

/**
 * View for using Fusion for standard MVC controllers.
 *
 * Recursively loads all Fusion files from the configured path (By default that's Resources/Private/Fusion
 * of the current package) and then checks whether a Fusion object for current controller and action can be found.
 *
 * If the controller class name is Foo\Bar\Baz\Controller\BlahController and the action is "index",
 * it checks for the Fusion path Foo.Bar.Baz.BlahController.index.
 * If this path is found, then it is used for rendering. Otherwise, the $fallbackView is used.
 */
class FusionView extends AbstractView
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array
     */
    protected $supportedOptions = array(
        'typoScriptPathPatterns' => array(array('resource://@package/Private/Fusion'), 'Fusion files will be recursively loaded from this paths.', 'array'),
        'typoScriptPath' => array(null, 'The Fusion path which should be rendered; derived from the controller and action names or set by the user.', 'string'),
        'packageKey' => array(null, 'The package key where the Fusion should be loaded from. If not given, is automatically derived from the current request.', 'string'),
        'debugMode' => array(false, 'Flag to enable debug mode of the Fusion runtime explicitly (overriding the global setting).', 'boolean'),
        'enableContentCache' => array(false, 'Flag to enable content caching inside Fusion (overriding the global setting).', 'boolean')
    );

    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

    /**
     * @Flow\Inject
     * @var ViewInterface
     */
    protected $fallbackView;

    /**
     * The parsed Fusion array in its internal representation
     *
     * @var array
     */
    protected $parsedFusion;

    /**
     * Runtime cache of the TypoScript path which should be rendered; derived from the controller
     * and action names or set by the user.
     *
     * @var string
     */
    protected $fusionPath = null;

    /**
     * if FALSE, the fallback view will never be used.
     *
     * @var boolean
     */
    protected $fallbackViewEnabled = true;

    /**
     * The TypoScript Runtime
     *
     * @var Runtime
     */
    protected $fusionRuntime = null;

    /**
     * Reset runtime cache if an option is changed
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     */
    public function setOption($optionName, $value)
    {
        $this->fusionPath = null;
        parent::setOption($optionName, $value);
    }

    /**
     * Sets the TypoScript path to be rendered to an explicit value;
     * to be used mostly inside tests.
     *
     * @param string $typoScriptPath
     * @return void
     */
    public function setTypoScriptPath($typoScriptPath)
    {
        $this->setOption('typoScriptPath', $typoScriptPath);
    }

    /**
     * The package key where the TypoScript should be loaded from. If not given,
     * is automatically derived from the current request.
     *
     * @param string $packageKey
     * @return void
     */
    public function setPackageKey($packageKey)
    {
        $this->setOption('packageKey', $packageKey);
    }

    /**
     * @param string $pathPattern
     * @return void
     */
    public function setTypoScriptPathPattern($pathPattern)
    {
        $this->setOption('typoScriptPathPatterns', array($pathPattern));
    }

    /**
     * @param array $pathPatterns
     * @return void
     */
    public function setTypoScriptPathPatterns(array $pathPatterns)
    {
        $this->setOption('typoScriptPathPatterns', $pathPatterns);
    }

    /**
     * Disable the use of the Fallback View
     *
     * @return void
     */
    public function disableFallbackView()
    {
        $this->fallbackViewEnabled = false;
    }

    /**
     * Re-Enable the use of the Fallback View. By default, it is enabled,
     * so calling this method only makes sense if disableFallbackView() has
     * been called before.
     *
     * @return void
     */
    public function enableFallbackView()
    {
        $this->fallbackViewEnabled = true;
    }

    /**
     * Render the view
     *
     * @return string The rendered view
     * @api
     */
    public function render()
    {
        $this->initializeTypoScriptRuntime();
        if ($this->fusionRuntime->canRender($this->getFusionPathForCurrentRequest()) || $this->fallbackViewEnabled === false) {
            return $this->renderFusion();
        } else {
            return $this->renderFallbackView();
        }
    }

    /**
     * Load the TypoScript Files form the defined
     * paths and construct a Runtime from the
     * parsed results
     *
     * @return void
     */
    public function initializeTypoScriptRuntime()
    {
        if ($this->fusionRuntime === null) {
            $this->loadFusion();
            $this->fusionRuntime = new Runtime($this->parsedFusion, $this->controllerContext);
        }
        if (isset($this->options['debugMode'])) {
            $this->fusionRuntime->setDebugMode($this->options['debugMode']);
        }
        if (isset($this->options['enableContentCache'])) {
            $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
        }
    }

    /**
     * Load Fusion from the directories specified by $this->getOption('typoScriptPathPatterns')
     *
     * @return void
     */
    protected function loadFusion()
    {
        $mergedFusionCode = '';
        $fusionPathPatterns = $this->getOption('typoScriptPathPatterns');
        ksort($fusionPathPatterns);
        foreach ($fusionPathPatterns as $fusionPathPattern) {
            $fusionPathPattern = str_replace('@package', $this->getPackageKey(), $fusionPathPattern);
            $filePaths = array_merge(Files::readDirectoryRecursively($fusionPathPattern, '.fusion'), Files::readDirectoryRecursively($fusionPathPattern, '.ts2'));
            sort($filePaths);
            foreach ($filePaths as $filePath) {
                $mergedFusionCode .= PHP_EOL . file_get_contents($filePath) . PHP_EOL;
            }
        }
        $this->parsedFusion = $this->fusionParser->parse($mergedFusionCode);
    }

    /**
     * Get the package key to load the Fusion from. If set, $this->getOption('packageKey') is used.
     * Otherwise, the current request is taken and the controller package key is extracted
     * from there.
     *
     * @return string the package key to load Fusion from
     */
    protected function getPackageKey()
    {
        $packageKey = $this->getOption('packageKey');
        if ($packageKey !== null) {
            return $packageKey;
        } else {
            /** @var $request ActionRequest */
            $request = $this->controllerContext->getRequest();
            return $request->getControllerPackageKey();
        }
    }

    /**
     * Determines the Fusion path depending on the current controller and action
     *
     * @return string
     */
    protected function getFusionPathForCurrentRequest()
    {
        if ($this->fusionPath === null) {
            $fusionPath = $this->getOption('typoScriptPath');
            if ($fusionPath !== null) {
                $this->fusionPath = $fusionPath;
            } else {
                /** @var $request ActionRequest */
                $request = $this->controllerContext->getRequest();
                $fusionPathForCurrentRequest = $request->getControllerObjectName();
                $fusionPathForCurrentRequest = str_replace('\\Controller\\', '\\', $fusionPathForCurrentRequest);
                $fusionPathForCurrentRequest = str_replace('\\', '/', $fusionPathForCurrentRequest);
                $fusionPathForCurrentRequest = trim($fusionPathForCurrentRequest, '/');
                $fusionPathForCurrentRequest .= '/' . $request->getControllerActionName();

                $this->fusionPath = $fusionPathForCurrentRequest;
            }
        }
        return $this->fusionPath;
    }

    /**
     * Determine whether we are able to find Fusion at the requested position
     *
     * @return boolean TRUE if Fusion exists at the current Fusion path; FALSE otherwise
     */
    protected function isTypoScriptFoundForCurrentRequest()
    {
        return (Arrays::getValueByPath($this->parsedFusion, str_replace('/', '.', $this->getFusionPathForCurrentRequest())) !== null);
    }

    /**
     * Render the given Fusion and return the rendered page
     * @return string
     * @throws \Exception
     */
    protected function renderFusion()
    {
        $this->fusionRuntime->pushContextArray($this->variables);
        try {
            $output = $this->fusionRuntime->render($this->getFusionPathForCurrentRequest());
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious();
        }
        $this->fusionRuntime->popContext();
        return $output;
    }

    /**
     * Initialize and render the fallback view
     *
     * @return string
     */
    public function renderFallbackView()
    {
        $this->fallbackView->setControllerContext($this->controllerContext);
        $this->fallbackView->assignMultiple($this->variables);
        return $this->fallbackView->render();
    }
}
