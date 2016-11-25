<?php
namespace Neos\Neos\TypoScript;

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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\TypoScriptObjects\AbstractArrayTypoScriptObject;

/**
 * A TypoScript Plugin object.
 */
class PluginImplementation extends AbstractArrayTypoScriptObject
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var NodeInterface
     */
    protected $documentNode;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->tsValue('package');
    }

    /**
     * @return string
     */
    public function getSubpackage()
    {
        return $this->tsValue('subpackage');
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->tsValue('controller');
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->tsValue('action');
    }

    /**
     * @return string
     */
    public function getArgumentNamespace()
    {
        return $this->tsValue('argumentNamespace');
    }

    /**
     * Build the pluginRequest object
     *
     * @return ActionRequest
     */
    protected function buildPluginRequest()
    {
        /** @var $parentRequest ActionRequest */
        $parentRequest = $this->tsRuntime->getControllerContext()->getRequest();
        $pluginRequest = new ActionRequest($parentRequest);
        $pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
        $this->passArgumentsToPluginRequest($pluginRequest);

        if ($this->node instanceof NodeInterface) {
            $pluginRequest->setArgument('__node', $this->node);
            if ($pluginRequest->getControllerPackageKey() === null) {
                $pluginRequest->setControllerPackageKey($this->node->getProperty('package') ?: $this->getPackage());
            }
            if ($pluginRequest->getControllerSubpackageKey() === null) {
                $pluginRequest->setControllerSubpackageKey($this->node->getProperty('subpackage') ?: $this->getSubpackage());
            }
            if ($pluginRequest->getControllerName() === null) {
                $pluginRequest->setControllerName($this->node->getProperty('controller') ?: $this->getController());
            }
            if ($pluginRequest->getControllerActionName() === null) {
                $actionName = $this->node->getProperty('action');
                if ($actionName === null || $actionName === '') {
                    $actionName = $this->getAction() !== null ? $this->getAction() : 'index';
                }
                $pluginRequest->setControllerActionName($actionName);
            }

            $pluginRequest->setArgument('__node', $this->node);
            $pluginRequest->setArgument('__documentNode', $this->documentNode);
        } else {
            $pluginRequest->setControllerPackageKey($this->getPackage());
            $pluginRequest->setControllerSubpackageKey($this->getSubpackage());
            $pluginRequest->setControllerName($this->getController());
            $pluginRequest->setControllerActionName($this->getAction());
        }

        foreach ($this->properties as $key => $value) {
            $pluginRequest->setArgument('__' . $key, $this->tsValue($key));
        }
        return $pluginRequest;
    }

    /**
     * Returns the rendered content of this plugin
     *
     * @return string The rendered content as a string
     */
    public function evaluate()
    {
        $currentContext = $this->tsRuntime->getCurrentContext();
        $this->node = $currentContext['node'];
        $this->documentNode = $currentContext['documentNode'];
        /** @var $parentResponse Response */
        $parentResponse = $this->tsRuntime->getControllerContext()->getResponse();
        $pluginResponse = new Response($parentResponse);

        $this->dispatcher->dispatch($this->buildPluginRequest(), $pluginResponse);

        foreach ($pluginResponse->getHeaders()->getAll() as $key => $value) {
            $parentResponse->getHeaders()->set($key, $value, true);
        }

        return $pluginResponse->getContent();
    }

    /**
     * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
     * By default this is <plugin_class_name>
     *
     * @return string
     */
    protected function getPluginNamespace()
    {
        if ($this->getArgumentNamespace() !== null) {
            return $this->getArgumentNamespace();
        }

        if ($this->node instanceof NodeInterface) {
            $nodeArgumentNamespace = $this->node->getProperty('argumentNamespace');
            if ($nodeArgumentNamespace !== null) {
                return $nodeArgumentNamespace;
            }

            $nodeArgumentNamespace = $this->node->getNodeType()->getName();
            $nodeArgumentNamespace = str_replace(':', '-', $nodeArgumentNamespace);
            $nodeArgumentNamespace = str_replace('.', '_', $nodeArgumentNamespace);
            $nodeArgumentNamespace = strtolower($nodeArgumentNamespace);
            return $nodeArgumentNamespace;
        }

        $argumentNamespace = str_replace(array(':', '.', '\\'), array('_', '_', '_'), ($this->getPackage() . '_' . $this->getSubpackage() . '-' . $this->getController()));
        $argumentNamespace = strtolower($argumentNamespace);

        return $argumentNamespace;
    }

    /**
     * Pass the arguments which were addressed to the plugin to its own request
     *
     * @param ActionRequest $pluginRequest The plugin request
     * @return void
     */
    protected function passArgumentsToPluginRequest(ActionRequest $pluginRequest)
    {
        $arguments = $pluginRequest->getMainRequest()->getPluginArguments();
        $pluginNamespace = $this->getPluginNamespace();
        if (isset($arguments[$pluginNamespace])) {
            $pluginRequest->setArguments($arguments[$pluginNamespace]);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->evaluate();
    }
}
