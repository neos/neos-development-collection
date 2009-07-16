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
class Page extends \F3\TypoScript\AbstractContentArrayObject {

	const SCAN_PATTERN_BODYTAG = '/(body|BODY)[^<>]*>/';
	const SCAN_PATTERN_HEADTAG = '/(head|HEAD)[^<>]*>/';
	const SCAN_PATTERN_VIEW = '/[a-zA-Z0-9_]+/';

	/**
	 * This specifies the name of the view this Page object represents. The default is
	 * "default" and you only have to specify a view name if you're going to use frames
	 * or want to add alternative views of your website.
	 *
	 * @var string
	 */
	protected $view = 'default';

	/**
	 * Defines the opening body tag of the page.
	 *
	 * @var string
	 */
	protected $bodyTag = '<body style="background-color: white;">';

	/**
	 * Defines the opening head tag of the page.
	 *
	 * @var string
	 */
	protected $headTag = '<head>';

	/**
	 * The data of this content array are inserted in the head section. Data could be JavaScript, Meta-tags or additional stylesheet references.
	 *
	 * @var array
	 */
	protected $headData = '';

	/**
	 * These parameters are inserted into the body tag definition.
	 *
	 * @var string
	 */
	protected $bodyTagAdditionalParameters = '';

	/**
	 * Inserts a stylesheet into the <head> section of the page.
	 *
	 * @var unknown_type
	 */
	protected $stylesheet;

	/**
	 * Sets the name of the view
	 *
	 * @param  string				$view: Name of the view or "default" for the default view
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setView($view) {
		if (!is_string($view) || preg_match(self::SCAN_PATTERN_VIEW, $view) !== 1) throw new \F3\TypoScript\Exception('The specified view "' . $view . '" is not a view name.', 1181091024);
	}

	/**
	 * Returns the name of the view
	 *
	 * @return string				Name of the view or "default" for the default view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getView() {
		return $this->view;
	}

	/**
	 * Sets the body tag
	 *
	 * @param  string				$bodyTag: A valid HTML body tag
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBodyTag($bodyTag) {
		if (!is_string($bodyTag) || ($bodyTag !== '' && preg_match(self::SCAN_PATTERN_BODYTAG, $bodyTag) !== 1)) throw new \F3\TypoScript\Exception('The specified value is not a valid body tag.', 1181051659);
		$this->bodyTag = $bodyTag;
	}

	/**
	 * Sets the head tag
	 *
	 * @param  string				$headTag: A valid HTML head tag
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHeadTag($headTag) {
		if (!is_string($headTag) || ($headTag !== '' && preg_match(self::SCAN_PATTERN_HEADTAG, $headTag) !== 1)) throw new \F3\TypoScript\Exception('The specified value is not a valid head tag.', 1181051660);
		$this->headTag = $headTag;
	}

	/**
	 * Sets the head data
	 *
	 * @param  string				$headData:
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHeadData($headData) {
		$this->headData = $headData;
	}

	/**
	 * Returns the body tag
	 *
	 * @return string				The currently defined HTML body tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBodyTag() {
		return $this->bodyTag;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string				The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRenderedContent() {
		$content = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.1 Transitional//EN">
<html>
' . $this->headTag . '
' . $this->headData . '
</head>
<!--
	This website is brought to you by TYPO3 - inspiring people to share.
	TYPO3 is a free open source Content Management Framework licensed under GNU/GPL.
	Information and contribution at http://www.typo3.com and http://www.typo3.org
-->
' . $this->bodyTag . '
' . $this->getRenderedContentArray() . '
</body>
';
		return $this->processContent($content);
	}
}
?>