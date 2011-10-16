<?php
namespace TYPO3\TypoScript\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript"                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript object fixture
 *
 * @FLOW3\Scope("prototype")
 */
class ObjectWithArrayProperty extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * @var array
	 */
	protected $theArray = array();


	/**
	 * @return array
	 */
	public function getTheArray() {
      return $this->theArray;
   }


	/**
	 * @param array $theArray
	 * @return void
	 */
  public function setTheArray(array $theArray) {
      $this->theArray = $theArray;
   }
}

?>