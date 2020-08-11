<?php
namespace Neos\Neos\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Command\EventDispatchingNodeCommandControllerPluginInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\Exception as EelException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Exception as NeosException;
use Neos\Utility\Arrays;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * A plugin for the ContentRepository NodeCommandController which adds some tasks to the node:repair command:
 *
 * - adding missing URI segments
 * - removing dimensions on nodes / and /sites
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandControllerPlugin implements EventDispatchingNodeCommandControllerPluginInterface
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $dimensionCombinator;

    /**
     * @Flow\Inject
     * @var NodeUriPathSegmentGenerator
     */
    protected $nodeUriPathSegmentGenerator;

    /**
     * @var ConsoleOutput
     * @deprecated It's discouraged to interact with the console output directly. Instead use the event dispatching. @see dispatch()
     */
    protected $output;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Callbacks to be invoked when an event is triggered
     *
     * @see dispatch()
     * @var \Closure[]
     */
    protected $eventCallbacks;

    /**
     * Returns a short description
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @return string A piece of text to be included in the overall description of the node:xy command
     */
    public static function getSubCommandShortDescription($controllerCommandName)
    {
        switch ($controllerCommandName) {
            case 'repair':
                return 'Run integrity checks related to Neos features';
        }
        return '';
    }

    /**
     * Returns a piece of description for the specific task the plugin solves for the specified command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @return string A piece of text to be included in the overall description of the node:xy command
     */
    public static function getSubCommandDescription($controllerCommandName)
    {
        switch ($controllerCommandName) {
            case 'repair':
                return <<<'HELPTEXT'
<u>Create missing sites node</u>
createMissingSitesNode

If needed, creates a missing "/sites" node, which is essential for Neos to work
properly.

<u>Generate missing URI path segments</u>
generateUriPathSegments

Generates URI path segment properties for all document nodes which don't have a path
segment set yet.

<u>Remove content dimensions from / and /sites</u>
removeContentDimensionsFromRootAndSitesNode

Removes content dimensions from the root and sites nodes

HELPTEXT;
        }
        return '';
    }

    /**
     * A method which runs the task implemented by the plugin for the given command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @param ConsoleOutput $output (unused)
     * @param NodeType $nodeType Only handle this node type (if specified)
     * @param string $workspaceName Only handle this workspace (if specified)
     * @param boolean $dryRun If true, don't do any changes, just simulate what you would do
     * @param boolean $cleanup If false, cleanup tasks are skipped
     * @param string $skip Skip the given check or checks (comma separated)
     * @param string $only Only execute the given check or checks (comma separated)
     * @return void
     */
    public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = null, $workspaceName = 'live', $dryRun = false, $cleanup = true, $skip = null, $only = null)
    {
        /** @noinspection PhpDeprecationInspection This is only set for backwards compatibility */
        $this->output = $output;
        $commandMethods = [
            'generateUriPathSegments' => [ 'cleanup' => false ],
            'removeContentDimensionsFromRootAndSitesNode' => [ 'cleanup' => true ],
            'createMissingSitesNode' => [ 'cleanup' => true ]
        ];

        $skipCommandNames = Arrays::trimExplode(',', ($skip === null ? '' : $skip));
        $onlyCommandNames = Arrays::trimExplode(',', ($only === null ? '' : $only));

        switch ($controllerCommandName) {
            case 'repair':
                foreach ($commandMethods as $commandMethodName => $commandMethodConfiguration) {
                    if (in_array($commandMethodName, $skipCommandNames)) {
                        continue;
                    }
                    if ($onlyCommandNames !== [] && !in_array($commandMethodName, $onlyCommandNames)) {
                        continue;
                    }
                    if (!$cleanup && $commandMethodConfiguration['cleanup']) {
                        continue;
                    }
                    $this->$commandMethodName($workspaceName, $dryRun, $nodeType);
                }
        }
    }

    /**
     * Creates the /sites node if it is missing.
     *
     * @return void
     * @throws IllegalObjectTypeException
     */
    protected function createMissingSitesNode()
    {
        $this->dispatch(self::EVENT_NOTICE, sprintf('Checking for "%s" node ...', SiteService::SITES_ROOT_PATH));
        $rootNode = $this->contextFactory->create()->getRootNode();
        // We fetch the workspace to be sure it's known to the persistence manager and persist all
        // so the workspace and site node are persisted before we import any nodes to it.
        $rootNode->getContext()->getWorkspace();
        $this->persistenceManager->persistAll();
        $sitesNode = $rootNode->getNode(SiteService::SITES_ROOT_PATH);
        if ($sitesNode === null) {
            $taskDescription = sprintf('Create missing site node "<i>%s</i>"', SiteService::SITES_ROOT_PATH);
            $taskClosure = function () use ($rootNode) {
                $rootNode->createNode(NodePaths::getNodeNameFromPath(SiteService::SITES_ROOT_PATH));
                $this->persistenceManager->persistAll();
            };
            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
        }
    }

    /**
     * Generate missing URI path segments
     *
     * This generates URI path segment properties for all document nodes which don't have
     * a path segment set yet.
     *
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     * @throws EelException
     * @throws NodeException
     * @throws NeosException
     */
    public function generateUriPathSegments($workspaceName, $dryRun)
    {
        $baseContext = $this->createContext($workspaceName, []);
        $baseContextSitesNode = $baseContext->getNode(SiteService::SITES_ROOT_PATH);
        if (!$baseContextSitesNode) {
            $this->dispatch(self::EVENT_WARNING, sprintf('Could not find "%s" root node', SiteService::SITES_ROOT_PATH));
            return;
        }
        $baseContextSiteNodes = $baseContextSitesNode->getChildNodes();
        if ($baseContextSiteNodes === []) {
            $this->dispatch(self::EVENT_WARNING, sprintf('Could not find any site nodes in "%s" root node', SiteService::SITES_ROOT_PATH));
            return;
        }

        foreach ($this->dimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $flowQuery = new FlowQuery($baseContextSiteNodes);
            /** @noinspection PhpUndefinedMethodInspection */
            $siteNodes = $flowQuery->context(['dimensions' => $dimensionCombination, 'targetDimensions' => []])->get();
            if (count($siteNodes) > 0) {
                $this->dispatch(self::EVENT_NOTICE, sprintf('Checking for nodes with missing URI path segment in dimension "%s"', trim(NodePaths::generateContextPath('', '', $dimensionCombination), '@;')));
                foreach ($siteNodes as $siteNode) {
                    $this->generateUriPathSegmentsForNode($siteNode, $dryRun);
                }
            }
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * Traverses through the tree starting at the given root node and sets the uriPathSegment property derived from
     * the node label.
     *
     * @param NodeInterface $node The node where the traversal starts
     * @param boolean $dryRun
     * @return void
     * @throws NodeException
     * @throws NeosException
     */
    protected function generateUriPathSegmentsForNode(NodeInterface $node, $dryRun)
    {
        if ((string)$node->getProperty('uriPathSegment') === '') {
            $name = $node->getLabel() ?: $node->getName();
            $uriPathSegment = $this->nodeUriPathSegmentGenerator->generateUriPathSegment($node);
            $taskDescription = sprintf('Add missing URI path segment for "<i>%s</i>" (<i>%s</i>) => <i>%s</i>', $node->getPath(), $name, $uriPathSegment);
            $taskClosure = function () use ($node, $uriPathSegment) {
                $node->setProperty('uriPathSegment', $uriPathSegment);
            };
            $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
        }
        foreach ($node->getChildNodes('Neos.Neos:Document') as $childNode) {
            $this->generateUriPathSegmentsForNode($childNode, $dryRun);
        }
    }

    /**
     * Remove dimensions on nodes "/" and "/sites"
     *
     * This empties the content dimensions on those nodes, so when traversing via the Node API from the root node,
     * the nodes below "/sites" are always reachable.
     *
     * @param string $workspaceName
     * @return void
     */
    public function removeContentDimensionsFromRootAndSitesNode($workspaceName)
    {
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        /** @noinspection PhpUndefinedMethodInspection */
        $rootNodes = $this->nodeDataRepository->findByPath('/', $workspace);
        /** @noinspection PhpUndefinedMethodInspection */
        $sitesNodes = $this->nodeDataRepository->findByPath('/sites', $workspace);
        $this->dispatch(self::EVENT_NOTICE, 'Checking for root and site nodes with content dimensions set ...');
        /** @var \Neos\ContentRepository\Domain\Model\NodeData $rootNode */
        foreach ($rootNodes as $rootNode) {
            if ($rootNode->getDimensionValues() !== []) {
                $taskDescription = 'Remove content dimensions from root node';
                $taskClosure = function () use ($rootNode) {
                    $rootNode->setDimensions([]);
                    $this->nodeDataRepository->update($rootNode);
                };
                $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
            }
        }
        /** @var \Neos\ContentRepository\Domain\Model\NodeData $sitesNode */
        foreach ($sitesNodes as $sitesNode) {
            if ($sitesNode->getDimensionValues() !== []) {
                $taskDescription = 'Remove content dimensions from node "/sites"';
                $taskClosure = function () use ($sitesNode) {
                    $sitesNode->setDimensions([]);
                    $this->nodeDataRepository->update($sitesNode);
                };
                $this->dispatch(self::EVENT_TASK, $taskDescription, $taskClosure);
            }
        }
    }

    /**
     * Creates a content context for given workspace and language identifiers
     *
     * @param string $workspaceName
     * @param array $dimensions
     * @return \Neos\ContentRepository\Domain\Service\Context
     */
    protected function createContext($workspaceName, array $dimensions)
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

        return $this->contextFactory->create($contextProperties);
    }

    /**
     * Attaches a new event handler
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param \Closure $callback a closure to be invoked when the corresponding event was triggered
     * @return void
     */
    public function on(string $eventIdentifier, \Closure $callback): void
    {
        $this->eventCallbacks[$eventIdentifier][] = $callback;
    }

    /**
     * Trigger a custom event
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param array $eventPayload optional arguments to be passed to the handler closure
     * @return void
     */
    protected function dispatch(string $eventIdentifier, ...$eventPayload): void
    {
        if (!isset($this->eventCallbacks[$eventIdentifier])) {
            return;
        }
        /** @var \Closure $callback */
        foreach ($this->eventCallbacks[$eventIdentifier] as $callback) {
            call_user_func_array($callback, $eventPayload);
        }
    }
}
