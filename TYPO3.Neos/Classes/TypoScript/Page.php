<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

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
 * A TypoScript Page object
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Page extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $modelType = 'F3\TYPO3\Domain\Model\Content\Page';

	/**
	 * @var string
	 */
	protected $typoScriptObjectTemplateSource = 'package://TYPO3/Private/TypoScript/Templates/Page.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('title', 'additionalHead', 'body');

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var mixed
	 */
	protected $body;

	/**
	 * @var mixed
	 */
	protected $additionalHead;

	/**
	 * The page template
	 * 
	 * @var \F3\TYPO3\TypoScript\Template
	 */
	protected $template;

	/**
	 * The type is used to distinguish between different TypoScript Page objects.
	 * This property won't be rendered nor does it exist in the Page Domain Model.
	 *
	 * @var string
	 */
	protected $type = 'default';

	/**
	 * Overrides the title of this page.
	 *
	 * @param string $title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the overriden title of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the page template.
	 *
	 * @param \F3\TYPO3\TypoScript\Template $template
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTemplate(\F3\TYPO3\TypoScript\Template $template) {
		$this->template = $template;
	}

	/**
	 * Returns the page template.
	 *
	 * @return \F3\TYPO3\TypoScript\Template
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * Sets the type of this page.
	 *
	 * @param string $type
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Returns the type of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Explicitly sets the body content of this page.
	 * If set, this Page TypoScript Object won't use a possibly specified
	 * template to render the body, but returns the explicitly specified
	 * body instead.
	 *
	 * @param mixed $body Either a plain string or a TypoScript Content Object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * Renders the body content of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBody() {
		if ($this->body === NULL) {
			foreach ($this->presentationModelPropertyNames as $propertyName) {
				$this->template->assign($propertyName, $this->getProcessedProperty($propertyName, $this->renderingContext));
			}
			return $this->template->renderSection('body', $this->renderingContext);
		} else {
			return $this->body;
		}
	}

	/**
	 * Sets additional head content of this page.
	 *
	 * This may either be a plain string or a TypoScript Content Object
	 *
	 * @param mixed $additionalHead
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAdditionalHead($additionalHead) {
		$this->additionalHead = $additionalHead;
	}

	/**
	 *
	 * @return \F3\TYPO3\TypoScript\AdditionalHead
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdditionalHead() {
		$this->template->setModel($this->model);
		return $this->additionalHead . $this->template->renderSection('additionalHead', $this->renderingContext);
	}

}
?>