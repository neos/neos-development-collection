<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Displays a text-based "bar graph" giving an indication of the amount and type of
 * changes done to something. Created for use in workspace management.
 */
class ChangeStatsViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Expects an array of change count data and adds calculated ratios to the rendered child view
     *
     * @param array $changeCounts Expected keys: new, changed, removed
     * @return string
     */
    public function render(array $changeCounts)
    {
        $this->templateVariableContainer->add('newCountRatio', $changeCounts['new'] / $changeCounts['total'] * 100);
        $this->templateVariableContainer->add('changedCountRatio', $changeCounts['changed'] / $changeCounts['total'] * 100);
        $this->templateVariableContainer->add('removedCountRatio', $changeCounts['removed'] / $changeCounts['total'] * 100);
        $content = $this->renderChildren();
        $this->templateVariableContainer->remove('newCountRatio');
        $this->templateVariableContainer->remove('changedCountRatio');
        $this->templateVariableContainer->remove('removedCountRatio');

        return $content;
    }
}
