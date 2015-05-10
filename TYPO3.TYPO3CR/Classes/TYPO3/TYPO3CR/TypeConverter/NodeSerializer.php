<?php
namespace TYPO3\TYPO3CR\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * @Flow\Scope("singleton")
 */
class NodeSerializer extends AbstractTypeConverter {

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
	public function convertFrom($source, $targetType = NULL, array $subProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		return $source->getContextPath();
	}

}
