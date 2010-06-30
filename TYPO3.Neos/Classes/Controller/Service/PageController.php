<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Service;

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
 * The TYPO3 Page service controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'F3\ExtJS\ExtDirect\View';

	/**
	 * @var \F3\TYPO3\Domain\Repository\Content\PageRepository
	 */
	protected $pageRepository;

	/**
	 * Inject the Page repository
	 * @param \F3\TYPO3\Domain\Repository\Content\PageRepository $pageRepository
	 * @return void
	 */
	public function injectPageRepository(\F3\TYPO3\Domain\Repository\Content\PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
	}

	/**
	 * Select special views according to format
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function initializeAction() {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->defaultViewObjectName = 'F3\ExtJS\ExtDirect\View';
				$this->errorMethodName = 'extErrorAction';
				break;
			case 'json' :
				$this->defaultViewObjectName = 'F3\FLOW3\MVC\View\JsonView';
				break;
		}
	}

	/**
	 * Load page data
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'data' => $page,
						'success' => TRUE
					)
				);
				break;
			default :
				$this->view->assign('page', $page);
		}
	}

	/**
	 * Update a page
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function updateAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		$this->pageRepository->update($page);

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
				break;
			default :
				$this->redirect('show', NULL, NULL, array('page' => $page));
		}
	}

	/**
	 * Get information for editing a page
	 *
	 * @todo use some kind of TCA like configuration of page properties and form fields and convert that to Ext JS
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function editAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		switch ($this->request->getFormat()) {
			case 'extdirect' :
				$this->view->assign('value', array(
					'title' => array(
						'xtype' => 'textfield',
						'allowBlank' => FALSE,
						'fieldLabel' => 'Title'
					),
					'hidden' => array(
						'xtype' => 'checkbox',
						'fieldLabel' => 'Visibility',
						'boxLabel' => 'hidden'
					)
				));
				break;
			default:
				$this->view->assign('page', $page);
		}
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->argumentsMappingResults->getErrors());
	}
}
?>