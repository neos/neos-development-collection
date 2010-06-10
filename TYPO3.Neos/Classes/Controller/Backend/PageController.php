<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Backend;

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
 * The TYPO3 Page controller -- used to create/update pages.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageController extends \F3\FLOW3\MVC\Controller\ActionController {
	protected $defaultViewObjectName = 'F3\FLOW3\MVC\View\JsonView';

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\Content\PageRepository
	 */
	protected $pageRepository;

	/**
	 * Load page data
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
		$this->view->assign('value',
			array(
				'data' => $page,
				'success' => TRUE
			)
		);
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

		$this->view->assign('value',
			array(
				'success' => TRUE
			)
		);
	}

	/**
	 * Get form definition (Ext JS) for editing a page
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Page $page
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function editAction(\F3\TYPO3\Domain\Model\Content\Page $page) {
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
	}
}
?>