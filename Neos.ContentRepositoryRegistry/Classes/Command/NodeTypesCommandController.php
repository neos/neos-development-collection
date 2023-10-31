<?php

namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Symfony\Component\Yaml\Yaml;

#[Flow\Scope("singleton")]
class NodeTypesCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
        parent::__construct();
    }

    /**
     * Shows the merged configuration (including supertypes) of a NodeType
     *
     * @param string $nodeTypeName The name of the NodeType to show
     * @param ?string $path Path of the NodeType-configuration which will be shown
     * @param string $contentRepository Identifier of the Content Repository to determine the set of NodeTypes
     */
    public function showCommand(string $nodeTypeName, ?string $path = null, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $nodeTypeManager = $this->contentRepositoryRegistry->get($contentRepositoryId)->getNodeTypeManager();

        if (!$nodeTypeManager->hasNodeType($nodeTypeName)) {
            $this->outputLine('<b>NodeType "%s" was not found!</b>', [$nodeTypeName]);
            $this->quit();
        }

        $nodeType = $nodeTypeManager->getNodeType($nodeTypeName);
        $yaml = Yaml::dump(
            $path
                ? $nodeType->getConfiguration($path)
                : [$nodeTypeName => $nodeType->getFullConfiguration()],
            99
        );
        $this->outputLine('<b>NodeType Configuration "%s":</b>', [$nodeTypeName . ($path ? ("." . $path) : "")]);
        $this->outputLine();
        $this->outputLine($yaml);
        $this->outputLine();
    }

    /**
     * Lists all declared NodeTypes grouped by namespace
     *
     * @param string|null $filter Only NodeType-names containing this string will be listed
     * @param bool $includeAbstract List abstract NodeTypes
     * @param string $contentRepository Identifier of the Content Repository to determine the set of NodeTypes
     */
    public function listCommand(?string $filter = null, bool $includeAbstract = true, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $nodeTypeManager = $this->contentRepositoryRegistry->get($contentRepositoryId)->getNodeTypeManager();

        $nodeTypesFound = 0;
        $nodeTypeNameSpacesWithNodeTypeNames = [];
        foreach ($nodeTypeManager->getNodeTypes($includeAbstract) as $nodeType) {
            $nodeTypeName = $nodeType->name->value;
            if (!$filter || str_contains($nodeTypeName, $filter)) {
                [$nameSpace] = explode(":", $nodeTypeName, 2);
                $nodeTypeNameSpacesWithNodeTypeNames[$nameSpace][] = $nodeTypeName;
                $nodeTypesFound++;
            }
        }

        $this->outputLine("<b>Found $nodeTypesFound NodeTypes</b>");

        foreach ($nodeTypeNameSpacesWithNodeTypeNames as $nameSpace => $nodeTypeNames) {
            $this->outputLine();
            $this->outputLine("<b>$nameSpace</b>");

            foreach ($nodeTypeNames as $nodeTypeName) {
                $this->output->outputFormatted($nodeTypeName, [], 2);
            }
        }
    }
}
