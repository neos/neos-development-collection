<?php

declare(strict_types=1);

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

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
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
     * @param string $path Path of the NodeType-configuration which will be shown
     * @param int $level Truncate the configuration at this depth and show '...' (Usefully for only seeing the keys of the properties)
     * @param string $contentRepository Identifier of the Content Repository to determine the set of NodeTypes
     */
    public function showCommand(string $nodeTypeName, string $path = '', int $level = 0, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $nodeTypeManager = $this->contentRepositoryRegistry->get($contentRepositoryId)->getNodeTypeManager();

        $nodeType = $nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType) {
            $this->outputLine('<error>NodeType "%s" was not found!</error>', [$nodeTypeName]);
            $this->quit();
        }

        if ($path && !$nodeType->hasConfiguration($path)) {
            $this->outputLine('<b>NodeType "%s" does not have configuration "%s".</b>', [$nodeTypeName, $path]);
            $this->quit();
        }

        if (empty($path)) {
            $configuration = [$nodeTypeName => self::truncateArrayAtLevel($nodeType->getFullConfiguration(), $level)];
        } else {
            $configuration = $nodeType->getConfiguration($path);
            if (is_array($configuration)) {
                $configuration = self::truncateArrayAtLevel($configuration, $level);
            }
        }

        $yaml = Yaml::dump($configuration, 99);

        $this->outputLine('<b>NodeType configuration "%s":</b>', [$nodeTypeName . ($path ? ("." . $path) : "")]);
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

    /**
     * @param array<string, mixed> $array
     * @param int $truncateLevel 0 for no truncation and 1 to only show the first keys of the array
     * @param int $currentLevel 1 for the start and will be incremented recursively
     * @return array<string, mixed>
     */
    private static function truncateArrayAtLevel(array $array, int $truncateLevel, int $currentLevel = 1): array
    {
        if ($truncateLevel <= 0) {
            return $array;
        }
        $truncatedArray = [];
        foreach ($array as $key => $value) {
            if ($currentLevel >= $truncateLevel) {
                $truncatedArray[$key] = '...'; // truncated
                continue;
            }
            if (!is_array($value)) {
                $truncatedArray[$key] = $value;
                continue;
            }
            $truncatedArray[$key] = self::truncateArrayAtLevel($value, $truncateLevel, $currentLevel + 1);
        }
        return $truncatedArray;
    }
}
