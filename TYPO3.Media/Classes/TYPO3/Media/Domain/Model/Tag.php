<?php
namespace TYPO3\Media\Domain\Model;

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
 * A Tag, to organize Assets
 *
 * @Flow\Entity
 */
class Tag {

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $label;

	/**
	 * Constructs tag
	 *
	 * @param string
	 */
	public function __construct($label) {
		$this->label = $label;
	}

	/**
	 * Sets the label of this tag
	 *
	 * @param string $label
	 * @return void
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 * The label of this tag
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}
}
