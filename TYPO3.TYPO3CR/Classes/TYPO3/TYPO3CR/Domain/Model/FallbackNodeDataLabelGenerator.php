<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Unicode\Functions;
use TYPO3\TYPO3CR\Domain\Model\AbstractNodeData;

/**
 * The default node label generator; used if no-other is configured
 *
 * @deprecated Since 1.2 You should implement the NodeLabelGeneratorInterface now.
 */
class FallbackNodeDataLabelGenerator implements NodeDataLabelGeneratorInterface
{
    /**
     * Render a node label
     *
     * @param AbstractNodeData $nodeData
     * @param boolean $crop This argument is deprecated as of Neos 1.2 and will be removed. Don't rely on this behavior and crop labels in the view.
     * @return string
     */
    public function getLabel(AbstractNodeData $nodeData, $crop = true)
    {
        if ($nodeData->hasProperty('title') === true && $nodeData->getProperty('title') !== '') {
            $label = strip_tags($nodeData->getProperty('title'));
        } elseif ($nodeData->hasProperty('text') === true && $nodeData->getProperty('text') !== '') {
            $label = strip_tags($nodeData->getProperty('text'));
        } else {
            $label = ($nodeData->getNodeType()->getLabel() ?: $nodeData->getNodeType()->getName()) . ' (' . $nodeData->getName() . ')';
        }

        if ($crop === false) {
            return $label;
        }

        $croppedLabel = trim(Functions::substr($label, 0, 30));
        return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
    }
}
