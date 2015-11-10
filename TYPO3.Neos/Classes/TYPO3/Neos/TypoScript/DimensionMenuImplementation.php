<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;
use TYPO3\TypoScript\Exception as TypoScriptException;

/**
 * A TypoScript Dimension Menu object
 *
 * Main Options:
 * - dimension (required, string): name of the dimension which this menu should be based on. Example: "language".
 * - presets (optional, array): If set, the presets are not loaded from the Settings, but instead taken from this property
 */
class DimensionMenuImplementation extends AbstractMenuImplementation
{
    /**
     * @Flow\Inject
     * @var ConfigurationContentDimensionPresetSource
     */
    protected $configurationContentDimensionPresetSource;

    /**
     * @return string
     */
    public function getDimension()
    {
        return $this->tsValue('dimension');
    }

    /**
     * @return array
     */
    public function getPresets()
    {
        return $this->tsValue('presets');
    }

    /**
     * @return array
     */
    public function buildItems()
    {
        $output = array();
        $dimension = $this->getDimension();
        foreach ($this->getPresetsInCorrectOrder() as $presetName => $presetConfiguration) {
            $q = new FlowQuery(array($this->currentNode));
            $nodeInOtherDimension = $q->context(
                array(
                    'dimensions' => array(
                        $dimension => $presetConfiguration['values']
                    ),
                    'targetDimensions' => array(
                        $dimension => reset($presetConfiguration['values'])
                    )
                )
            )->get(0);

            if ($nodeInOtherDimension !== null && $this->isNodeHidden($nodeInOtherDimension)) {
                $nodeInOtherDimension = null;
            }

            $item = array(
                'node' => $nodeInOtherDimension,
                'state' => $this->calculateItemState($nodeInOtherDimension),
                'label' => $presetConfiguration['label'],
                'presetName' => $presetName,
                'preset' => $presetConfiguration
            );
            $output[] = $item;
        }

        return $output;
    }

    /**
     * Return the presets in the correct order, taking possibly-overridden presets into account
     *
     * @return array
     * @throws TypoScriptException
     */
    protected function getPresetsInCorrectOrder()
    {
        $dimension = $this->getDimension();

        $allDimensions = $this->configurationContentDimensionPresetSource->getAllPresets();
        if (!isset($allDimensions[$dimension])) {
            throw new TypoScriptException(sprintf('Dimension "%s" was referenced, but not configured.', $dimension), 1415880445);
        }
        $allPresetsOfChosenDimension = $allDimensions[$dimension]['presets'];

        $presetNames = $this->getPresets();
        if ($presetNames === null) {
            $presetNames = array_keys($allPresetsOfChosenDimension);
        } elseif (!is_array($presetNames)) {
            throw new TypoScriptException('The configured preset in TypoScript was no array.', 1415888652);
        }

        $resultingPresets = array();
        foreach ($presetNames as $presetName) {
            if (!isset($allPresetsOfChosenDimension[$presetName])) {
                throw new TypoScriptException(sprintf('The preset name "%s" does not exist in the chosen dimension. Valid values are: %s', $presetName, implode(', ', array_keys($allPresetsOfChosenDimension))), 1415889492);
            }
            $resultingPresets[$presetName] = $allPresetsOfChosenDimension[$presetName];
        }

        return $resultingPresets;
    }
}
