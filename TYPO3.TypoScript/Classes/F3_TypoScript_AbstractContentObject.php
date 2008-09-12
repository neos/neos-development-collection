<?php
declare(ENCODING = 'utf-8');
namespace F3::TypoScript;

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
 * @package TypoScript
 * @version $Id$
 */

/**
 * Common class for TypoScript Content Objects
 *
 * @package TypoScript
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractContentObject extends F3::TypoScript::AbstractObject {

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function getRenderedContent();

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return $this->getRenderedContent();
	}

	/**
	 * Runs the processors chain for the given content by using the root processor chain of the
	 * content object and returns the result value.
	 *
	 * @param string $content The content to process
	 * @result string The processed content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function processContent($content) {
		if (!isset($this->propertyProcessorChains['_root'])) return $content;
		return $this->propertyProcessorChains['_root']->process($content);
	}
}
?>