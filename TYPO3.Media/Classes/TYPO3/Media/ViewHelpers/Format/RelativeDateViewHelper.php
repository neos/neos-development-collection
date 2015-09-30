<?php
namespace TYPO3\Media\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a DateTime formatted relative to the current date
 */
class RelativeDateViewHelper extends AbstractViewHelper
{
    /**
     * Renders a DateTime formatted relative to the current date.
     * Shows the time if the date is the current date.
     * Shows the month and date if the date is the current year.
     * Shows the year/month/date if the date is not the current year.
     *
     * @param \DateTime $date
     * @return string an <img...> html tag
     * @throws \InvalidArgumentException
     */
    public function render(\DateTime $date = null)
    {
        if ($date === null) {
            $date = $this->renderChildren();
        }
        if (!$date instanceof \DateTime) {
            throw new \InvalidArgumentException('No valid date given,', 1424647058);
        }
        // More than 11 months ago
        $now = new \TYPO3\Flow\Utility\Now();
        if ($date < $now->modify('-11 months')) {
            return $date->format('Y M n');
        }
        // Same day of same year
        $now = new \TYPO3\Flow\Utility\Now();
        if ($date->format('Y z') === $now->format('Y z')) {
            return $date->format('H:i');
        }
        return $date->format('M n');
    }
}
