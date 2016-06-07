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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * An abstract adjustment which provides a constructor for setting options
 */
abstract class AbstractAdjustment implements AdjustmentInterface, ConfigurationBasedAdjustmentInterface, \ArrayAccess
{

    /**
     * @var array
     * @ORM\Column(type="flow_json_array", nullable = FALSE)
     */
    protected $configuration;

    /**
     * @var string
     * @ORM\Column(nullable = FALSE)
     */
    protected $configurationHash;


    /**
     * Constructs this adjustment
     *
     * @param array $configuration configuration options - depends on the actual adjustment
     * @api
     */
    public function __construct(array $configuration = [])
    {
        $this->setConfiguration($configuration);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
        $this->refreshConfigurationHash();
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getConfigurationValue($path)
    {
        return Arrays::getValueByPath($this->configuration, $path);
    }

    /**
     * @param string|array $path
     * @param mixed $value
     * @return void
     */
    public function setConfigurationValue($path, $value)
    {
        $this->configuration = Arrays::setValueByPath($this->configuration, $path, $value);
        $this->refreshConfigurationHash();
    }

    /**
     * @param string|array $path
     * @return void
     */
    public function unsetConfigurationValue($path)
    {
        $this->configuration = Arrays::unsetValueByPath($this->configuration, $path);
        $this->refreshConfigurationHash();
    }

    /**
     * @return string
     */
    public function getConfigurationHash()
    {
        return $this->configurationHash;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->configuration[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->configuration[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setConfigurationValue($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->unsetConfigurationValue($offset);
    }


    /**
     * @return void
     */
    protected function refreshConfigurationHash()
    {
        $this->configurationHash = md5(json_encode($this->configuration));
    }
}
