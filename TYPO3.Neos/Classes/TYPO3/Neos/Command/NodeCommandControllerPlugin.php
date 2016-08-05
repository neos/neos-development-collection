<?php
namespace TYPO3\Neos\Command;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Service\SiteService;
use TYPO3\Neos\Utility\NodeUriPathSegmentGenerator;
use TYPO3\TYPO3CR\Command\NodeCommandControllerPluginInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionCombinator;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * A plugin for the TYPO3CR NodeCommandController which adds a task adding missing URI segments to the node:repair
 * command.
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
     * @var ContentDimensionCombinator
     * @Flow\Inject
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
                return
                    '<u>Generate missing URI path segments</u>' . PHP_EOL .
                    PHP_EOL .
                    'Generates URI path segment properties for all document nodes which don\'t have a path' . PHP_EOL .
                    'segment set yet.' . PHP_EOL;
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
            'generateUriPathSegments' => [ 'cleanup' => false ]
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
        foreach ($node->getChildNodes('TYPO3.Neos:Document') as $childNode) {
            $this->generateUriPathSegmentsForNode($childNode, $dryRun);
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
