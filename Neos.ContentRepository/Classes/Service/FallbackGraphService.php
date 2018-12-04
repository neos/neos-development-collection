<?php
namespace Neos\ContentRepository\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\InterDimension;
use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The fallback graph application service
 *
 * To be used as a read-only source of fallback information for graph-related projectors
 *
 * Never use this on the read side since its initialization time grows linearly
 * by the amount of possible combinations of content dimension values
 *
 * @Flow\Scope("singleton")
 * @api
 */
class FallbackGraphService
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @var IntraDimension\IntraDimensionalFallbackGraph
     */
    protected $intraDimensionalFallbackGraph;

    /**
     * @var InterDimension\InterDimensionalFallbackGraph
     */
    protected $interDimensionalFallbackGraph;

    /**
     * @throws Exception\InvalidDimensionConfigurationException
     */
    public function initializeObject()
    {
        $prioritizedContentDimensions = $this->populateIntraDimensionalFallbackGraph();
        $this->populateInterDimensionalFallbackGraph($prioritizedContentDimensions);
    }

    /**
     * @return array|IntraDimension\ContentDimension[]
     */
    protected function populateIntraDimensionalFallbackGraph(): array
    {
        $prioritizedContentDimensions = [];
        $this->intraDimensionalFallbackGraph = new IntraDimension\IntraDimensionalFallbackGraph();

        $this->populatePresetDimensions($prioritizedContentDimensions);

        return $prioritizedContentDimensions;
    }

    /**
     * @param array $prioritizedContentDimensions
     * @return void
     * @throws Exception\InvalidDimensionConfigurationException
     */
    protected function populatePresetDimensions(array & $prioritizedContentDimensions)
    {
        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionConfiguration) {
            $presetDimension = $this->intraDimensionalFallbackGraph->createDimension($dimensionName, $dimensionConfiguration['label'] ?? null);
            foreach ($dimensionConfiguration['presets'] as $valueName => $valueConfiguration) {
                if (!isset($valueConfiguration['values'])) {
                    continue;
                }
                $fallbackConfiguration = array_slice($valueConfiguration['values'], 0, 2);
                if (isset($fallbackConfiguration[1])) {
                    if ($presetDimension->getValue($fallbackConfiguration[1])) {
                        $fallbackValue = $presetDimension->getValue($fallbackConfiguration[1]);
                    } else {
                        throw new Exception\InvalidDimensionConfigurationException('Unknown fallback value ' . $fallbackConfiguration[1] . ' was for defined for value ' . $fallbackConfiguration[0], 1487617770);
                    }
                } else {
                    $fallbackValue = null;
                }
                $presetDimension->createValue($fallbackConfiguration[0], $fallbackValue);
            }
            $prioritizedContentDimensions[] = $presetDimension;
        }
    }

    /**
     * @param array|IntraDimension\ContentDimension[] $prioritizedContentDimensions
     */
    protected function populateInterDimensionalFallbackGraph(array $prioritizedContentDimensions)
    {
        $this->interDimensionalFallbackGraph = new InterDimension\InterDimensionalFallbackGraph($prioritizedContentDimensions);

        $dimensionValueCombinations = [[]];
        foreach ($prioritizedContentDimensions as $contentDimension) {
            $nextLevelValueCombinations = [];
            foreach ($dimensionValueCombinations as $previousCombination) {
                foreach ($contentDimension->getValues() as $value) {
                    $newCombination = $previousCombination;
                    $newCombination[$contentDimension->getName()] = $value;
                    if (!$this->contentDimensionPresetSource->isPresetCombinationAllowedByConstraints(
                        $this->translateDimensionValueCombinationToPresetCombination($newCombination)
                    )) {
                        continue;
                    }

                    $nextLevelValueCombinations[] = $newCombination;
                }
            }

            $dimensionValueCombinations = $nextLevelValueCombinations;
        }

        $edgeCount = 0;
        foreach ($dimensionValueCombinations as $dimensionValues) {
            $newContentSubgraph = $this->interDimensionalFallbackGraph->createContentSubgraph($dimensionValues);
            foreach ($this->interDimensionalFallbackGraph->getSubgraphs() as $presentContentSubgraph) {
                if ($presentContentSubgraph === $newContentSubgraph
                    || $this->interDimensionalFallbackGraph->normalizeWeight($newContentSubgraph->getWeight())
                    < $this->interDimensionalFallbackGraph->normalizeWeight($presentContentSubgraph->getWeight())
                ) {
                    continue 2;
                }
                try {
                    $this->interDimensionalFallbackGraph->connectSubgraphs($newContentSubgraph, $presentContentSubgraph);
                    $edgeCount++;
                } catch (IntraDimension\Exception\InvalidFallbackException $e) {
                    continue;
                }
            }
        }
    }

    /**
     * @param array|IntraDimension\ContentDimensionValue[] $dimensionValueCombination
     * @return array
     */
    protected function translateDimensionValueCombinationToPresetCombination(array $dimensionValueCombination)
    {
        $presetCombination = [];
        foreach ($dimensionValueCombination as $dimensionName => $dimensionValue) {
            $presetCombination[$dimensionName] = $dimensionValue->getValue();
        }

        return $presetCombination;
    }

    /**
     * @param string $subgraphIdentifier
     * @return array
     */
    public function determineAffectedVariantSubgraphIdentifiers(string $subgraphIdentifier): array
    {
        $affectedVariantIdentifiers = [$subgraphIdentifier];
        $subgraph = $this->getInterDimensionalFallbackGraph()->getSubgraph($subgraphIdentifier);
        foreach ($subgraph->getVariants() as $variantSubgraph) {
            $affectedVariantIdentifiers[] = $variantSubgraph->getIdentityHash();
        }

        return $affectedVariantIdentifiers;
    }

    /**
     * @param string $subgraphIdentifier
     * @return array
     */
    public function determineConnectedSubgraphIdentifiers(string $subgraphIdentifier): array
    {
        $subgraph = $this->getInterDimensionalFallbackGraph()->getSubgraph($subgraphIdentifier);
        while ($subgraph->getFallback()) {
            $subgraph = $subgraph->getFallback();
        }
        $connectedVariantIdentifiers = [$subgraph->getIdentityHash()];
        foreach ($subgraph->getVariants() as $variantSubgraph) {
            $connectedVariantIdentifiers[] = $variantSubgraph->getIdentityHash();
        }
        return $connectedVariantIdentifiers;
    }

    /**
     * @return IntraDimension\IntraDimensionalFallbackGraph
     * @api
     */
    public function getIntraDimensionalFallbackGraph(): IntraDimension\IntraDimensionalFallbackGraph
    {
        return $this->intraDimensionalFallbackGraph;
    }

    /**
     * @return InterDimension\InterDimensionalFallbackGraph
     * @api
     */
    public function getInterDimensionalFallbackGraph(): InterDimension\InterDimensionalFallbackGraph
    {
        return $this->interDimensionalFallbackGraph;
    }
}
