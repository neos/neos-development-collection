<?php
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValueVariationEdge;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use Symfony\Component\Yaml\Yaml;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires that the Flow Object Manager must be available via $this->getObjectManager().
 *
 * Note: This trait expects the IsolatedBehatStepsTrait to be available!
 */
trait NodeOperationsTrait
{

    /**
     * @var array
     */
    private $nodeTypesConfiguration = [];

    /**
     * @return mixed
     */
    abstract protected function getObjectManager();

    /**
     * @AfterScenario @fixtures
     */
    public function resetCustomNodeTypes()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $this->getObjectManager()->get(\Neos\ContentRepository\Domain\Service\NodeTypeManager::class)->overrideNodeTypes([]);
        }
    }

    /**
     * @Given /^I have the following (additional |)NodeTypes configuration:$/
     */
    public function iHaveTheFollowingNodetypesConfiguration($additional, $nodeTypesConfiguration)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($additional), 'integer', escapeshellarg($nodeTypesConfiguration)));
        } else {
            if (strlen($additional) > 0) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($this->nodeTypesConfiguration, Yaml::parse($nodeTypesConfiguration->getRaw()));
            } else {
                $this->nodeTypesConfiguration = Yaml::parse($nodeTypesConfiguration->getRaw());
                $configuration = $this->nodeTypesConfiguration;
            }
            $this->getObjectManager()->get(\Neos\ContentRepository\Domain\Service\NodeTypeManager::class)->overrideNodeTypes($configuration);
        }
    }


    /**
     * @Given /^I have the following content dimensions:$/
     */
    public function iHaveTheFollowingContentDimensions($table)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', escapeshellarg(\Neos\Flow\Tests\Functional\Command\TableNode::class), escapeshellarg(json_encode($table->getHash()))));
        } else {
            $dimensions = [];
            foreach ($table->getHash() as $row) {
                $rawGeneralizations = [];
                $specializationDepths = [];
                $dimensionValues = [];
                $variationEdges = [];
                foreach (Arrays::trimExplode(',', $row['Generalizations']) as $variationExpression) {
                    $currentGeneralization = null;
                    foreach (array_reverse(Arrays::trimExplode('->', $variationExpression)) as $specializationDepth => $rawDimensionValue) {
                        $specializationDepths[$rawDimensionValue] = $specializationDepth;
                        if ($currentGeneralization) {
                            $rawGeneralizations[$rawDimensionValue] = $currentGeneralization;
                        }
                        $currentGeneralization = $rawDimensionValue;
                    }
                }

                foreach (Arrays::trimExplode(',', $row['Values']) as $rawDimensionValue) {
                    $dimensionValues[$rawDimensionValue] = new ContentDimensionValue(
                        $rawDimensionValue,
                        new ContentDimensionValueSpecializationDepth($specializationDepths[$rawDimensionValue] ?? 0)
                    );
                }

                foreach ($rawGeneralizations as $rawSpecializationValue => $rawGeneralizationValue) {
                    $variationEdges[] = new ContentDimensionValueVariationEdge($dimensionValues[$rawSpecializationValue], $dimensionValues[$rawGeneralizationValue]);
                }

                $dimensions[$row['Identifier']] = new ContentDimension(
                    new ContentDimensionIdentifier($row['Identifier']),
                    $dimensionValues,
                    $dimensionValues[$row['Default']],
                    $variationEdges
                );
            }

            /** @var ConfigurationBasedContentDimensionSource $contentDimensionSource */
            $contentDimensionSource = $this->getObjectManager()->get(ContentDimensionSourceInterface::class);
            ObjectAccess::setProperty($contentDimensionSource, 'contentDimensions', $dimensions, true);

            $this->resetDimensionSpaceRepositories();
        }
    }

    /**
     * @Given /^I have no content dimensions$/
     */
    public function iHaveNoContentDimensions()
    {
        /** @var ConfigurationBasedContentDimensionSource $contentDimensionSource */
        $contentDimensionSource = $this->getObjectManager()->get(ContentDimensionSourceInterface::class);
        ObjectAccess::setProperty($contentDimensionSource, 'contentDimensions', [], true);

        $this->resetDimensionSpaceRepositories();
    }

    private function resetDimensionSpaceRepositories()
    {
        /** @var ContentDimensionZookeeper $contentDimensionZookeeper */
        $contentDimensionZookeeper = $this->getObjectManager()->get(ContentDimensionZookeeper::class);
        ObjectAccess::setProperty($contentDimensionZookeeper, 'allowedCombinations', null, true);

        /** @var InterDimensionalVariationGraph $interDimensionalVariationGraph */
        $interDimensionalVariationGraph = $this->getObjectManager()->get(InterDimensionalVariationGraph::class);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'weightedDimensionSpacePoints', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'indexedGeneralizations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'indexedSpecializations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'weightedGeneralizations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'weightedSpecializations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'primaryGeneralizations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'rootGeneralizations', null, true);
        ObjectAccess::setProperty($interDimensionalVariationGraph, 'weightNormalizationBase', null, true);
    }
}
