<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\NodeTypePostprocessor;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\NodeType\NodeTypePostprocessorInterface;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Utility\Arrays;

/**
 * Apply presets from configuration to nodeTypes
 */
class NodeTypePresetPostprocessor implements NodeTypePostprocessorInterface
{
    /**
     * @var array
     * @phpstan-var array<string,mixed>
     * @Flow\InjectConfiguration(package="Neos.Neos", path="nodeTypes.presets.properties")
     */
    protected $propertyPresetConfiguration;

    /**
     * @var array
     * @phpstan-var array<string,mixed>
     * @Flow\InjectConfiguration(package="Neos.Neos", path="nodeTypes.presets.childNodes")
     */
    protected $childNodePresetConfiguration;

    /**
     * @param NodeType $nodeType (uninitialized) The node type to process
     * @param array<string,mixed> $configuration input configuration
     * @param array<string,mixed> $options The processor options
     * @return void
     */
    public function process(NodeType $nodeType, array &$configuration, array $options): void
    {
        if ($nodeType->hasConfiguration('properties')) {
            foreach ($nodeType->getConfiguration('properties') as $propertyName => $propertyConfiguration) {
                if ($preset = Arrays::getValueByPath($propertyConfiguration, 'options.preset')) {
                    $presetConfiguration = Arrays::getValueByPath($this->propertyPresetConfiguration, $preset);
                    if ($presetConfiguration) {
                        $mergedPropertyConfiguration = Arrays::arrayMergeRecursiveOverrule(
                            $presetConfiguration,
                            $propertyConfiguration
                        );
                        $configuration['properties'][$propertyName] = $mergedPropertyConfiguration;
                    }
                }
            }
        }

        if ($nodeType->hasConfiguration('childNodes')) {
            foreach ($nodeType->getConfiguration('childNodes') as $propertyName => $propertyConfiguration) {
                if ($preset = Arrays::getValueByPath($propertyConfiguration, 'options.preset')) {
                    $presetConfiguration = Arrays::getValueByPath($this->childNodePresetConfiguration, $preset);
                    if ($presetConfiguration) {
                        $mergedPropertyConfiguration = Arrays::arrayMergeRecursiveOverrule(
                            $presetConfiguration,
                            $propertyConfiguration
                        );
                        $configuration['childNodes'][$propertyName] = $mergedPropertyConfiguration;
                    }
                }
            }
        }
    }
}
