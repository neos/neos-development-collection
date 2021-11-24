<?php
namespace Neos\Neos\Fusion;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Neos\Domain\Model\PluginViewDefinition;
use Neos\Neos\Service\PluginService;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A Fusion PluginView.
 */
class PluginViewImplementation extends PluginImplementation
{
    /**
     * @var PluginService
     * @Flow\Inject
     */
    protected $pluginService;

    /**
     * @var NodeInterface
     */
    protected $pluginViewNode;

    /**
     * Build the proper pluginRequest to render the PluginView
     * of some configured Master Plugin
     *
     * @return ActionRequest
     */
    protected function buildPluginRequest(): ActionRequest
    {
        /** @var $parentRequest ActionRequest */
        $parentRequest = $this->runtime->getControllerContext()->getRequest();
        $pluginRequest = $parentRequest->createSubRequest();

        if (!$this->pluginViewNode instanceof NodeInterface) {
            $pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
            $this->passArgumentsToPluginRequest($pluginRequest);
            $pluginRequest->setControllerPackageKey($this->getPackage());
            $pluginRequest->setControllerSubpackageKey($this->getSubpackage());
            $pluginRequest->setControllerName($this->getController());
            $pluginRequest->setControllerActionName($this->getAction());
            return $pluginRequest;
        }

        $pluginNodeIdentifier = $this->pluginViewNode->getProperty('plugin');
        if (strlen($pluginNodeIdentifier) === 0) {
            return $pluginRequest;
        }

        // Set the node to render this to the master plugin node
        $this->node = $this->pluginViewNode->getContext()->getNodeByIdentifier($pluginNodeIdentifier);
        if ($this->node === null) {
            return $pluginRequest;
        }

        $pluginRequest->setArgument('__node', $this->node);
        $pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
        $this->passArgumentsToPluginRequest($pluginRequest);

        if ($pluginRequest->getControllerObjectName() !== '') {
            return $pluginRequest;
        }

        $controllerObjectPairs = [];
        $pluginViewName = $this->pluginViewNode->getProperty('view');
        foreach ($this->pluginService->getPluginViewDefinitionsByPluginNodeType($this->node->getNodeType()) as $pluginViewDefinition) {
            /** @var PluginViewDefinition $pluginViewDefinition */
            if ($pluginViewDefinition->getName() !== $pluginViewName) {
                continue;
            }
            $controllerObjectPairs = $pluginViewDefinition->getControllerActionPairs();
            break;
        }

        if ($controllerObjectPairs === []) {
            return $pluginRequest;
        }

        $defaultControllerObjectName = key($controllerObjectPairs);
        $defaultActionName = current($controllerObjectPairs[$defaultControllerObjectName]);
        $pluginRequest->setControllerObjectName($defaultControllerObjectName);
        $pluginRequest->setControllerActionName($defaultActionName);

        return $pluginRequest;
    }

    /**
     * Returns the rendered content of this plugin
     *
     * @return string The rendered content as a string
     * @throws StopActionException
     */
    public function evaluate(): string
    {
        $currentContext = $this->runtime->getCurrentContext();
        $this->pluginViewNode = $currentContext['node'];
        /** @var $parentResponse ActionResponse */
        $parentResponse = $this->runtime->getControllerContext()->getResponse();
        $pluginResponse = new ActionResponse();

        $pluginRequest = $this->buildPluginRequest();
        if ($pluginRequest->getControllerObjectName() === '') {
            $message = 'Master View not selected';
            if ($this->pluginViewNode->getProperty('plugin')) {
                $message = 'Plugin View not selected';
            }
            if ($this->pluginViewNode->getProperty('view')) {
                $message ='Master View or Plugin View not found';
            }
            return $this->pluginViewNode->getContext()->getWorkspaceName() !== 'live' || $this->objectManager->getContext()->isDevelopment() ? '<p>' . $message . '</p>' : '<!-- ' . $message . '-->';
        }
        $this->dispatcher->dispatch($pluginRequest, $pluginResponse);

        // We need to make sure to not merge content up into the parent ActionResponse because that would break the Fusion HttpResponse.
        $content = $pluginResponse->getContent();
        $pluginResponse->setContent('');

        $pluginResponse->mergeIntoParentResponse($parentResponse);

        return $content;
    }
}
