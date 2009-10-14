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
	 * @var array<string>
	 */
	protected $renderedSections = array();

	/**
	 * @var string
	 */
	protected $title = 'Untitled';

	/**
	 * @var string
	 */
	protected $type = 'default';
	/**
	 *
	 * @param $model
	 * @return unknown_type
	 */
	public function setModel($model) {
		parent::setModel($model);
		$this->title = $this->model->getTitle();
	}

	/**
	 *
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRenderedContent() {
		$this->view->setTemplatePathAndFilename(FLOW3_PATH_PACKAGES . 'Application/TYPO3/Resources/Private/Templates/TypoScript/Page.html');
		$presentationModel = array(
			'title' => $this->getProcessedProperty('title')
		);
		$this->view->assignMultiple($presentationModel);
		return $this->view->render();
	}
}
?>