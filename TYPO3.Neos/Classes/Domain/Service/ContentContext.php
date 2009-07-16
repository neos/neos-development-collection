<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Content Context
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class ContentContext {

	/**
	 * @inject
	 * @var F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \DateTime
	 */
	protected $currentDateTime;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentService
	 */
	protected $contentService;

	/**
	 * @var \F3\FLOW3\Locale\Locale
	 */
	protected $locale;

	/**
	 * Constructs this content context
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->currentDateTime = new \DateTime();
	}

	/**
	 * Initializes the context after all dependencies have been injected.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->contentService = $this->objectFactory->create('F3\TYPO3\Domain\Service\ContentService', $this);
		$this->locale = $this->objectFactory->create('F3\FLOW3\Locale\Locale', 'mul-ZZ');
	}

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTime() {
		return $this->currentDateTime;
	}

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime().
	 *
	 * @param \DateTime $currentDateTime A date and time to simulate.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCurrentDateTime(\DateTime $currentDateTime) {
		$this->currentDateTime = $currentDateTime;
	}

	/**
	 * Returns the content service which is bound to this context.
	 * Only use this method for retrieving an instance of the Content Service!
	 *
	 * @return \F3\TYPO3\Domain\Service\ContentService
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentService() {
		return $this->contentService;
	}

	/**
	 * Returns the locale of this context.
	 *
	 * @return \F3\FLOW3\Locale\Locale
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLocale() {
		return $this->locale;
	}
}
?>