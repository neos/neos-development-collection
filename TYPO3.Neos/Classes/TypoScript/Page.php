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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Page extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var \F3\TYPO3\Domain\Model\Content\Page
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $modelType = 'F3\TYPO3\Domain\Model\Content\Page';

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Page.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('title', 'head', 'body', 'content', 'parts', 'renderingContext');

	/**
	 * The type is used to distinguish between different TypoScript Page objects.
	 * This property won't be rendered nor does it exist in the Page Domain Model.
	 *
	 * @var string
	 */
	protected $type = 'default';

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var mixed
	 */
	protected $body;

	/**
	 * @var \F3\TYPO3\TypoScript\Head
	 */
	protected $head;

	/**
	 * @var \F3\TYPO3\TypoScript\Content
	 */
	protected $content;

	/**
	 * @var array
	 */
	protected $parts = array();

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
	 * Overrides the title of this page.
	 *
	 * @param string $title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	public function getRenderingContext() {
		return $this->renderingContext;
	}

	/**
	 * Returns the overridden title of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets head content of this page.
	 *
	 * @param \F3\TYPO3\TypoScript\ $head
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHead(\F3\TYPO3\TypoScript\Head $head) {
		$this->head = $head;
	}

	/**
	 * Gets head content of this page.
	 *
	 * @return \F3\TYPO3\TypoScript\Head
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHead() {
		return $this->head;
	}

	/**
	 * Explicitly sets the body content of this page.
	 *
	 * @param mixed $body Either a plain string or a TypoScript Content Object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * Returns the explicitly set body content of this page (if any).
	 *
	 * Either a plain string or a TypoScript Content Object
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Overrides the Content TypoScript Object used on this page.
	 *
	 * @param \F3\TYPO3\TypoScript\Content $content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent(\F3\TYPO3\TypoScript\Content $content) {
     	$this->content = $content;
  	}

	/**
	 * Returns the content used on this page.
	 *
	 * @return array An array of TypoScript Objects, indexed by content names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent() {
     	return $this->content;
  	}

	/**
	 * Sets the parts array for this page
	 *
	 * @param array $parts
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setParts(array $parts) {
		$this->parts = $parts;
	}

	/**
	 * Returns the parts array of this page
	 *
	 * @return array An array of TypoScript objects if any have been defined
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParts() {
		return $this->parts;
	}

	/**
	 * Returns the rendered content of this Page TypoScript Object
	 *
	 * @return string The rendered content as a string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$this->model = $this->renderingContext->getContentContext()->getCurrentNodeContent();
		$this->head->setModel($this->model);
		return parent::render();
	}
}
?>