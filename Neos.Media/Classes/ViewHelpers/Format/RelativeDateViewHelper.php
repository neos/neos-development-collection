<?php
namespace Neos\Media\ViewHelpers\Format;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

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
        $now = new Now();
        if ($date < $now->modify('-11 months')) {
            return $date->format('Y M j');
        }
        // Same day of same year
        $now = new Now();
        if ($date->format('Y z') === $now->format('Y z')) {
            return $date->format('H:i');
        }
        return $date->format('M j');
    }
}
