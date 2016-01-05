<?php
namespace TYPO3\TYPO3CR\TypeConverter;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * @Flow\Scope("singleton")
 */
class NodeSerializer extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @param NodeInterface $source The node instance
     * @param string $targetType not used
     * @param array $subProperties not used
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string The node context path
     */
    public function convertFrom($source, $targetType = null, array $subProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        return $source->getContextPath();
    }
}
