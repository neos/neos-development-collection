<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript Page object
 *
 * @FLOW3\Scope("prototype")
 */
class Page extends \TYPO3\TypoScript\AbstractContentObject {

	/**
	 * Content type of the node this TS Object is based on.
	 *
	 * @var string
	 */
	protected $contentType = 'TYPO3.TYPO3:Page';

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3.TYPO3/Private/Templates/TypoScriptObjects/Page.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('title', 'head', 'body', 'content', 'parts');

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
	 * @var \TYPO3\TYPO3\TypoScript\Head
	 */
	protected $head;

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Content
	 */
	protected $content;

	/**
	 * @var array
	 */
	protected $parts = array();

	/**
	 * Sets the type of this page.
	 *
	 * What is used as a type string is completely up to the TypoScript integrator.
	 * The default type is "default".
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
	 * @param \TYPO3\TYPO3\TypoScript\Head $head
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHead(\TYPO3\TYPO3\TypoScript\Head $head) {
		$this->head = $head;
	}

	/**
	 * Gets head content of this page.
	 *
	 * @return \TYPO3\TYPO3\TypoScript\Head
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
	 * @param \TYPO3\TYPO3\TypoScript\Content $content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent(\TYPO3\TYPO3\TypoScript\Content $content) {
		$this->content = $content;
	}

	/**
	 * Returns the Content TypoScript Object used on this page.
	 *
	 * @return \TYPO3\TYPO3\TypoScript\Content
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
		$this->node = $this->renderingContext->getContentContext()->getCurrentNode();
		$this->head->setNode($this->node);
		return parent::render();
	}
}
?>