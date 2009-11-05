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

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @inject
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

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
		return $this->contentContext;
	}

	/**
	 * While matching, resolves the requested page
	 *
	 * @param string $value the complete path
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		$this->contentContext = $this->objectFactory->create('F3\TYPO3\Domain\Service\ContentContext');
		$site = $this->contentContext->getCurrentSite();
		if ($site === NULL) {
			return FALSE;
		}

		$node = $this->contentContext->getNodeService()->getNode($site, '/' . $value);
		if ($node === NULL) {
			return FALSE;
		}
		$page = $node->getContent($this->contentContext);
		if (!$page instanceof \F3\TYPO3\Domain\Model\Content\Page) {
			return FALSE;
		}
		$this->contentContext->setNodePath('/' . $value);
		$this->value = array('__identity' => $page->FLOW3_Persistence_Entity_UUID);
		return TRUE;
	}

	/**
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 */
	protected function findValueToMatch($requestPath) {
		$dotPosition = strpos($requestPath, '.');
		return ($dotPosition === FALSE) ? $requestPath : substr($requestPath, 0, $dotPosition);
	}

	/**
	 * Resolves the URI to a page
	 *
	 * @param
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo
	 */
	protected function resolveValue($value) {
		return FALSE;
	}
}
?>