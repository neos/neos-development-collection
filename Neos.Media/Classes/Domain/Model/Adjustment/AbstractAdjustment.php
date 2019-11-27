<?php
declare(strict_types=1);

namespace Neos\Media\Domain\Model\Adjustment;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
    public function __construct(array $options = [])
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
