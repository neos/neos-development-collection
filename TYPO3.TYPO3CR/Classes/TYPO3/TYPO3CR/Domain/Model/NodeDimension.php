<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
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
 * NOTE: This is internal only and should not be used or extended by userland code.
 *
 * @todo This should be renamed to NodeDimensionMapping or something else, because it's not just a dimension but a relation entity
 *
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="nodeData_name_value",columns={"nodedata", "name", "value"})})
 */
class NodeDimension {

	/**
	 * @ORM\ManyToOne(inversedBy="dimensions")
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeData
	 */
	protected $nodeData;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @param NodeData $nodeData
	 * @param string $name
	 * @param string $value
	 */
	public function __construct(NodeData $nodeData, $name, $value) {
		$this->nodeData = $nodeData;
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData
	 */
	public function setNodeData($nodeData) {
		$this->nodeData = $nodeData;
	}

	/**
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeData
	 */
	public function getNodeData() {
		return $this->nodeData;
	}
}
