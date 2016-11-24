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

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use TYPO3\TYPO3CR\Command\NodeCommandControllerPluginInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionCombinator;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * A plugin for the TYPO3CR NodeCommandController which adds some tasks to the node:repair command:
 *
 * - adding missing URI segments
 * - removing dimensions on nodes / and /sites
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandControllerPlugin implements NodeCommandControllerPluginInterface
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
     */
    protected $output;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
<u>Generate missing URI path segments</u>

Generates URI path segment properties for all document nodes which don't have a path
segment set yet.

<u>Remove content dimensions from / and /sites</u>
removeContentDimensionsFromRootAndSitesNode

Removes content dimensions from the root and sites nodes

HELPTEXT;
        }
    }

    /**
     * A method which runs the task implemented by the plugin for the given command
     *
     * @param string $controllerCommandName Name of the command in question, for example "repair"
     * @param ConsoleOutput $output An instance of ConsoleOutput which can be used for output or dialogues
     * @param NodeType $nodeType Only handle this node type (if specified)
     * @param string $workspaceName Only handle this workspace (if specified)
     * @param boolean $dryRun If TRUE, don't do any changes, just simulate what you would do
     * @param boolean $cleanup If FALSE, cleanup tasks are skipped
     * @param string $skip Skip the given check or checks (comma separated)
     * @param string $only Only execute the given check or checks (comma separated)
     * @return void
     */
    public function invokeSubCommand($controllerCommandName, ConsoleOutput $output, NodeType $nodeType = null, $workspaceName = 'live', $dryRun = false, $cleanup = true, $skip = null, $only = null)
    {
        $this->output = $output;
        $commandMethods = [
            'generateUriPathSegments' => [ 'cleanup' => false ],
            'removeContentDimensionsFromRootAndSitesNode' => [ 'cleanup' => true ]
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
     * Generate missing URI path segments
     *
     * This generates URI path segment properties for all document nodes which don't have
     * a path segment set yet.
     *
     * @param string $workspaceName
     * @param boolean $dryRun
     * @return void
     */
    public function generateUriPathSegments($workspaceName, $dryRun)
    {
        $baseContext = $this->createContext($workspaceName, []);
        $baseContextSitesNode = $baseContext->getNode(SiteService::SITES_ROOT_PATH);
        if (!$baseContextSitesNode) {
            $this->output->outputLine('<error>Could not find "' . SiteService::SITES_ROOT_PATH . '" root node</error>');
            return;
        }
        $baseContextSiteNodes = $baseContextSitesNode->getChildNodes();
        if ($baseContextSiteNodes === []) {
            $this->output->outputLine('<error>Could not find any site nodes in "' . SiteService::SITES_ROOT_PATH . '" root node</error>');
            return;
        }

        foreach ($this->dimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $flowQuery = new FlowQuery($baseContextSiteNodes);
            $siteNodes = $flowQuery->context(['dimensions' => $dimensionCombination, 'targetDimensions' => []])->get();
            if (count($siteNodes) > 0) {
                $this->output->outputLine('Checking for nodes with missing URI path segment in dimension "%s"', array(trim(NodePaths::generateContextPath('', '', $dimensionCombination), '@;')));
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
     */
    protected function generateUriPathSegmentsForNode(NodeInterface $node, $dryRun)
    {
        if ((string)$node->getProperty('uriPathSegment') === '') {
            $name = $node->getLabel() ?: $node->getName();
            $uriPathSegment = $this->nodeUriPathSegmentGenerator->generateUriPathSegment($node);
            if ($dryRun === false) {
                $node->setProperty('uriPathSegment', $uriPathSegment);
                $this->output->outputLine('Added missing URI path segment for "%s" (%s) => %s', array($node->getPath(), $name, $uriPathSegment));
            } else {
                $this->output->outputLine('Found missing URI path segment for "%s" (%s) => %s', array($node->getPath(), $name, $uriPathSegment));
            }
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
     * @param boolean $dryRun
     * @return void
     */
    public function removeContentDimensionsFromRootAndSitesNode($workspaceName, $dryRun)
    {
        $workspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        $rootNodes = $this->nodeDataRepository->findByPath('/', $workspace);
        $sitesNodes = $this->nodeDataRepository->findByPath('/sites', $workspace);
        $this->output->outputLine('Checking for root and site nodes with content dimensions set ...');
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $rootNode */
        foreach ($rootNodes as $rootNode) {
            if ($rootNode->getDimensionValues() !== []) {
                if ($dryRun === false) {
                    $rootNode->setDimensions([]);
                    $this->nodeDataRepository->update($rootNode);
                    $this->output->outputLine('Removed content dimensions from root node');
                } else {
                    $this->output->outputLine('Found root node with content dimensions set.');
                }
            }
        }
        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $sitesNode */
        foreach ($sitesNodes as $sitesNode) {
            if ($sitesNode->getDimensionValues() !== []) {
                if ($dryRun === false) {
                    $sitesNode->setDimensions([]);
                    $this->nodeDataRepository->update($sitesNode);
                    $this->output->outputLine('Removed content dimensions from node "/sites"');
                } else {
                    $this->output->outputLine('Found node "/sites"');
                }
            }
        }
    }

    /**
     * Creates a content context for given workspace and language identifiers
     *
     * @param string $workspaceName
     * @param array $dimensions
     * @return \TYPO3\TYPO3CR\Domain\Service\Context
     */
    protected function createContext($workspaceName, array $dimensions)
    {
        $contextProperties = array(
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        );

        return $this->contextFactory->create($contextProperties);
    }
}
