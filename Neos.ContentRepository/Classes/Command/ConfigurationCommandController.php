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

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Symfony\Component\Yaml\Yaml;

#[Flow\Scope("singleton")]
class ConfigurationCommandController extends CommandController
{
    #[Flow\Inject()]
    protected NodeTypeManager $nodeTypeManager;

    /**
     * Shows the merged configuration (including supertypes) of a NodeTypeName
     *
     * @param string $nodeTypeName
     * @param ?string $path optional path of the NodeType configuration
     */
    public function showNodeTypeCommand(string $nodeTypeName, ?string $path = null): void
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType) {
            $this->outputLine('<b>NodeTypeName "%s" was not found!</b>', [$nodeTypeName]);
            $this->quit();
        }
        $yaml = Yaml::dump(
            $path
                ? $nodeType->getConfiguration($path)
                : [
                $nodeTypeName => $nodeType->getFullConfiguration()
            ],
            99
        );
        $this->outputLine('<b>NodeType Configuration "%s":</b>', [$nodeTypeName . ($path ? ("." . $path) : "")]);
        $this->outputLine();
        $this->outputLine($yaml . chr(10));
    }
}
