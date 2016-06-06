<?php
namespace TYPO3\Media\Domain\Model\Adjustment;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Interface for an Asset Adjustment
 */
interface AdjustmentInterface
{

    /**
     * Returns the adjustment configuration
     *
     * @return array
     */
    public function getConfiguration();

    /**
     * Sets the adjustment configuration
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration);

    /**
     * Returns a specific adjustment configuration value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigurationValue($path);

    /**
     * Sets a specific adjustment configuration value
     *
     * @param string|array $path
     * @param mixed $value
     * @return void
     */
    public function setConfigurationValue($path, $value);

    /**
     * Unsets a specific adjustment configuration value
     *
     * @param string|array $path
     * @return void
     */
    public function unsetConfigurationValue($path);

}
