<?php
namespace TYPO3\Neos\Aspects;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\ObjectManagement\ObjectManagerInterface;
use TYPO3\Neos\Service\PluginService;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PluginUriAspect
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * The pluginService
     *
     * @var PluginService
     * @Flow\Inject
     */
    protected $pluginService;

    /**
     * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\UriBuilder->uriFor())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     */
    public function rewritePluginViewUris(JoinPointInterface $joinPoint)
    {
        /** @var ActionRequest $request */
        $request = $joinPoint->getProxy()->getRequest();
        $arguments = $joinPoint->getMethodArguments();

        $currentNode = $request->getInternalArgument('__node');
        if (!$request->getMainRequest()->hasArgument('node') || !$currentNode instanceof Node) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $currentNode = $request->getInternalArgument('__node');
        $controllerObjectName = $this->getControllerObjectName($request, $arguments);
        $actionName = $arguments['actionName'] !== null ? $arguments['actionName'] : $request->getControllerActionName();

        $targetNode = $this->pluginService->getPluginNodeByAction($currentNode, $controllerObjectName, $actionName);

        // TODO override namespace

        $q = new FlowQuery(array($targetNode));
        $pageNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
        $result = $this->generateUriForNode($request, $joinPoint, $pageNode);

        return $result;
    }

    /**
     * Merge the default plugin arguments of the Plugin with the arguments in the request
     * and generate a controllerObjectName
     *
     * @param object $request
     * @param array $arguments
     * @return string $controllerObjectName
     */
    public function getControllerObjectName($request, array $arguments)
    {
        $controllerName = $arguments['controllerName'] !== null ? $arguments['controllerName'] : $request->getControllerName();
        $subPackageKey = $arguments['subPackageKey'] !== null ? $arguments['subPackageKey'] : $request->getControllerSubpackageKey();
        $packageKey = $arguments['packageKey'] !== null ? $arguments['packageKey'] : $request->getControllerPackageKey();

        $possibleObjectName = '@package\@subpackage\Controller\@controllerController';
        $possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
        $possibleObjectName = str_replace('@subpackage', $subPackageKey, $possibleObjectName);
        $possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
        $possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

        $controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
        return ($controllerObjectName !== false) ? $controllerObjectName : '';
    }

    /**
     * This method generates the Uri through the joinPoint with
     * temporary overriding the used node
     *
     * @param ActionRequest $request
     * @param JoinPointInterface $joinPoint The current join point
     * @param NodeInterface $node
     * @return string $uri
     */
    public function generateUriForNode(ActionRequest $request, JoinPointInterface $joinPoint, NodeInterface $node)
    {
        // store original node path to restore it after generating the uri
        $originalNodePath = $request->getMainRequest()->getArgument('node');

        // generate the uri for the given node
        $request->getMainRequest()->setArgument('node', $node->getContextPath());
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        // restore the original node path
        $request->getMainRequest()->setArgument('node', $originalNodePath);

        return $result;
    }
}
