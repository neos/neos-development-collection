<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Routing;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectContentContext(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Returns the current content context
	 * 
	 * @return \F3\TYPO3\Domain\Service\ContentContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentContext() {
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
		$site = $this->contentContext->getCurrentSite();
		if ($site === NULL) {
			return self::MATCHRESULT_NOSITE;
		}

		$node = $this->contentContext->getNodeService()->getNode($site, '/' . $value);
		if ($node === NULL) {
			return self::MATCHRESULT_NOSUCHNODE;
		}
		$page = $node->getContent($this->contentContext);
		if (!$page instanceof \F3\TYPO3\Domain\Model\Content\Page) {
			return self::MATCHRESULT_NOSUCHPAGE;
		}
		$this->contentContext->setNodePath('/' . $value);
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