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
 * The TYPO3 Block service controller
 *
 * @version $Id: PageController.php 4676 2010-06-30 09:03:43Z networkteam_hlubek $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BlockController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'F3\ExtJS\ExtDirect\View';

	/**
	 * @var \F3\TYPO3\Domain\Repository\Content\BlockRepository
	 * @inject
	 */
	protected $blockRepository;

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
	 * Update a block
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\Block $block
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function updateAction(\F3\TYPO3\Domain\Model\Content\Block $block) {
		$this->blockRepository->update($block);

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