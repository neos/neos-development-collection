<?php
namespace TYPO3\Media\Domain\Model\Adjustment;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An abstract adjustment which provides a constructor for setting options
 */
abstract class AbstractAdjustment implements AdjustmentInterface
{
    /**
     * Constructs this adjustment
     *
     * @param array $options configuration options - depends on the actual adjustment
     * @throws \InvalidArgumentException
     * @api
     */
    public function __construct(array $options = array())
    {
        foreach ($options as $optionKey => $optionValue) {
            $methodName = 'set' . ucfirst($optionKey);
            if (method_exists($this, $methodName)) {
                $this->$methodName($optionValue);
            } else {
                throw new \InvalidArgumentException('Invalid adjustment option "' . $optionKey . '" for adjustment of type "' . get_class($this) . '"', 1381395072);
            }
        }
    }
}
