<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */ 

/**
 * Some content
 * 
 * @package		CMS
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class T3_TYPO3_Domain_Content {

	/**
	 * @var string The UUID of this content element
	 */
	protected $uuid;
	
	/**
	 * @var boolean Flags if the content is hidden
	 */
	protected $hidden = FALSE;
	
	/**
	 * @var string Some content
	 */
	protected $content;
	
	/**
	 * Constructs the Content
	 *
	 * @param  T3_FLOW3_Utility_Algorithms $utilityAlgorithms: A reference to the algorithms utility component
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Utility_Algorithms $utilityAlgorithms) {
		$this->uuid = $utilityAlgorithms->generateUUID();
	}
	
	/**
	 * Sets the content
	 *
	 * @param  string			$content: The content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * Returns the content
	 *
	 * @return string			The content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent() {
		return $this->content;
	}
}

?>