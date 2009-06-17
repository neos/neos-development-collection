<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Frontend\Controller;

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
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id$
 */

/**
 * TYPO3's frontend page controller
 *
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @var \F3\TYPO3\Domain\Service\FrontendContentContext
	 */
	protected $contentContext;

	/**
	 *
	 */
	public function initializeAction() {
		$this->contentContext = $this->objectFactory->create('F3\TYPO3\Domain\Service\FrontendContentContext');
		var_dump($this->contentContext->getContentService());
	}

	/**
	 * Show the root page, because no page was specified
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		$sites = $this->siteRepository->findAll();
		$output = '';
		foreach ($sites as $site) {
			$output .= $site->getName();
		}
		return $output;
	}

	/**
	 * Shows the page specified in the "page" argument
	 *
	 * @param ...
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction($page) {
		return "<br />\nTYPO3 Frontend: show()";
	}
}
?>