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
use Neos\Utility\Files;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;

/**
 * View for using Fusion for standard MVC controllers.
 *
 * Recursively loads all Fusion files from the configured path (By default that's Resources/Private/Fusion
 * of the current package) and then checks whether a Fusion object for current controller and action can be found.
 *
 * If the controller class name is Foo\Bar\Baz\Controller\BlahController and the action is "index",
 * it checks for the Fusion path Foo.Bar.Baz.BlahController.index.
 * If this path is found, then it is used for rendering.
 */
class FusionView extends AbstractView
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array
     */
    protected $supportedOptions = [
        'fusionPathPatterns' => [['resource://@package/Private/Fusion'], 'Fusion files that will be loaded if directories are given the Root.fusion is used.', 'array'],
        'fusionPath' => [null, 'The Fusion path which should be rendered; derived from the controller and action names or set by the user.', 'string'],
        'packageKey' => [null, 'The package key where the Fusion should be loaded from. If not given, is automatically derived from the current request.', 'string'],
        'debugMode' => [false, 'Flag to enable debug mode of the Fusion runtime explicitly (overriding the global setting).', 'boolean'],
        'enableContentCache' => [false, 'Flag to enable content caching inside Fusion (overriding the global setting).', 'boolean']
    ];

    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

    /**
     * The parsed Fusion array in its internal representation
     *
     * @var array
     */
    protected $parsedFusion;

    /**
     * Runtime cache of the Fusion path which should be rendered; derived from the controller
     * and action names or set by the user.
     *
     * @var string
     */
    protected $fusionPath = null;

    /**
     * @Flow\Inject
     * @var RuntimeFactory
     */
    protected $runtimeFactory;

    /**
     * The Fusion Runtime
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
     * Sets the Fusion path to be rendered to an explicit value;
     * to be used mostly inside tests.
     *
     * @param string $fusionPath
     * @return void
     */
    public function setFusionPath($fusionPath)
    {
        $this->setOption('fusionPath', $fusionPath);
    }

    /**
     * The package key where the Fusion should be loaded from. If not given,
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
    public function setFusionPathPattern($pathPattern)
    {
        $this->setOption('fusionPathPatterns', [$pathPattern]);
    }

    /**
     * @param array $pathPatterns
     * @return void
     */
    public function setFusionPathPatterns(array $pathPatterns)
    {
        $this->setOption('fusionPathPatterns', $pathPatterns);
    }

    /**
     * Render the view
     *
     * @return mixed The rendered view
     * @api
     */
    public function render()
    {
        $this->initializeFusionRuntime();
        return $this->renderFusion();
    }

    /**
     * Load the Fusion Files form the defined
     * paths and construct a Runtime from the
     * parsed results
     *
     * @return void
     */
    public function initializeFusionRuntime()
    {
        if ($this->fusionRuntime === null) {
            $this->loadFusion();
            $this->fusionRuntime = $this->runtimeFactory->create($this->parsedFusion, $this->controllerContext);
        }
        if (isset($this->options['debugMode'])) {
            $this->fusionRuntime->setDebugMode($this->options['debugMode']);
        }
        if (isset($this->options['enableContentCache'])) {
            $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
        }
    }

    /**
     * Load Fusion from the directories specified by $this->getOption('fusionPathPatterns')
     *
     * @return void
     */
    protected function loadFusion()
    {
        $this->parsedFusion = $this->getMergedFusionObjectTree();
    }

    /**
     * Parse all the fusion files the are in the current fusionPathPatterns
     *
     * @return array
     */
    protected function getMergedFusionObjectTree(): array
    {
        $parsedFusion = [];
        $fusionPathPatterns = $this->getOption('fusionPathPatterns');
        foreach ($fusionPathPatterns as $fusionPathPattern) {
            $fusionPathPattern = str_replace('@package', $this->getPackageKey(), $fusionPathPattern);
            if (is_dir($fusionPathPattern)) {
                $fusionPathPattern .= '/Root.fusion';
            }
            if (file_exists($fusionPathPattern)) {
                $parsedFusion = $this->fusionParser->parse(file_get_contents($fusionPathPattern), $fusionPathPattern, $parsedFusion);
            }
        }
        return $parsedFusion;
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
            $fusionPath = $this->getOption('fusionPath');
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
     * Render the given Fusion and return the rendered page
     * @return mixed
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
}
