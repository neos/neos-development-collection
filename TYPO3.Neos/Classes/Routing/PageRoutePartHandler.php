<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Routing;

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
 * A route part handler for Pages
 *
 * This route part handler also accommodates the (Frontend) Content Context. Wherever
 * the current context is needed (e.g. in the Page Controller) this class is the ultimate
 * authority to be asked.
 *
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope singleton
 */
class PageRoutePartHandler extends \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart {

	const MATCHRESULT_FOUND = TRUE;
	const MATCHRESULT_NOSITE = -1;
	const MATCHRESULT_NOSUCHNODE = -2;
	const MATCHRESULT_NOSUCHPAGE = -3;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * Returns the current content context
	 *
	 * @return \F3\TYPO3\Domain\Service\ContentContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentContext() {
		if ($this->contentContext === NULL) {
			$this->contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext');
		}
		return $this->contentContext;
	}

	/**
	 * While matching, resolves the requested page
	 *
	 * @param string $value the complete path
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		$contentContext = $this->getContentContext();

		$site = $contentContext->getCurrentSite();
		if ($site === NULL) {
			return self::MATCHRESULT_NOSITE;
		}

		$node = $contentContext->getNodeService()->getNode('/' . $value);
		if ($node === NULL) {
			return self::MATCHRESULT_NOSUCHNODE;
		}
		$page = $node->getContent($contentContext);
		if (!$page instanceof \F3\TYPO3\Domain\Model\Content\Page) {
			return self::MATCHRESULT_NOSUCHPAGE;
		}
		$contentContext->setCurrentPage($page);
		$contentContext->setCurrentNodePath('/' . $value);
		$this->value = array('__identity' => $page->FLOW3_Persistence_Entity_UUID);
		return TRUE;
	}

	/**
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function findValueToMatch($requestPath) {
		$dotPosition = strpos($requestPath, '.');
		return ($dotPosition === FALSE) ? $requestPath : substr($requestPath, 0, $dotPosition);
	}
}
?>