<?php
namespace Neos\ContentRepository\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\DescriptionAwareCommandControllerInterface;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Node command controller for the Neos.ContentRepository package
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends CommandController implements DescriptionAwareCommandControllerInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $pluginConfigurations = [];

    /**
     * Repair inconsistent nodes
     *
     * This command analyzes and repairs the node tree structure and individual nodes
     * based on the current node type configuration.
     *
     * It is possible to execute only one or more specific checks by providing the <b>--skip</b>
     * or <b>--only</b> option. See the full description of checks further below for possible check
     * identifiers.
     *
     * The following checks will be performed:
     *
     * {pluginDescriptions}
     * <b>Examples:</b>
     *
     * ./flow node:repair
     *
     * ./flow node:repair --node-type Neos.NodeTypes:Page
     *
     * ./flow node:repair --workspace user-robert --only removeOrphanNodes,removeNodesWithInvalidDimensions
     *
     * ./flow node:repair --skip removeUndefinedProperties
     *
     * @param string $nodeType Node type name, if empty update all declared node types
     * @param string $workspace Workspace name, default is 'live'
     * @param boolean $dryRun Don't do anything, but report actions
     * @param boolean $cleanup If false, cleanup tasks are skipped
     * @param string $skip Skip the given check or checks (comma separated)
     * @param string $only Only execute the given check or checks (comma separated)
     * @return void
     * @throws StopActionException
     */
    public function repairCommand($nodeType = null, $workspace = 'live', $dryRun = false, $cleanup = true, $skip = null, $only = null)
    {
        $this->pluginConfigurations = self::detectPlugins($this->objectManager);

        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->workspaceRepository->countByName($workspace) === 0) {
            $this->outputLine('Workspace "%s" does not exist', [$workspace]);
            exit(1);
        }

        if ($nodeType !== null) {
            try {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
            } catch (NodeTypeNotFoundException $e) {
                $this->outputLine('<error>Node type "%s" does not exist</error>', [$nodeType]);
                $this->quit(1);
                return;
            }
        }

        if ($dryRun) {
            $this->outputLine('Dry run, not committing any changes.');
        }

        if (!$cleanup) {
            $this->outputLine('Omitting cleanup tasks.');
        }

        foreach ($this->pluginConfigurations as $pluginConfiguration) {
            /** @var NodeCommandControllerPluginInterface $plugin */
            $plugin = $pluginConfiguration['object'];
            $this->outputLine('<b>' . $plugin->getSubCommandShortDescription('repair') . '</b>');
            $this->outputLine();
            $plugin->invokeSubCommand('repair', $this->output, $nodeType, $workspace, $dryRun, $cleanup, $skip, $only);
            $this->outputLine();
        }

        $this->outputLine('Node repair finished.');
    }

    /**
     * Processes the given short description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $shortDescription The short command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified short command description
     */
    public static function processShortDescription($controllerCommandName, $shortDescription, ObjectManagerInterface $objectManager)
    {
        return $shortDescription;
    }

    /**
     * Processes the given description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $description The command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified command description
     */
    public static function processDescription($controllerCommandName, $description, ObjectManagerInterface $objectManager)
    {
        $pluginConfigurations = self::detectPlugins($objectManager);
        $pluginDescriptions = '';
        foreach ($pluginConfigurations as $className => $configuration) {
            /** @noinspection PhpUndefinedMethodInspection */
            $pluginDescriptions .= $className::getSubCommandDescription($controllerCommandName) . PHP_EOL;
        }
        return str_replace('{pluginDescriptions}', $pluginDescriptions, $description);
    }

    /**
     * Detects plugins for this command controller
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     */
    protected static function detectPlugins(ObjectManagerInterface $objectManager)
    {
        $pluginConfigurations = [];
        $classNames = $objectManager->get(ReflectionService::class)->getAllImplementationClassNamesForInterface(NodeCommandControllerPluginInterface::class);
        foreach ($classNames as $className) {
            $pluginConfigurations[$className] = [
                'object' => $objectManager->get($objectManager->getObjectNameByClassName($className))
            ];
        }
        return $pluginConfigurations;
    }
}
