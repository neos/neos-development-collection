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

use \F3\TYPO3\Domain\Service\ContentContext;
use \F3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A route part handler for the REST Node Service.
 *
 * The current workspace can be specified by a GET parameter "workspace". If no workspace was
 * specified, the live workspace will be used.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope singleton
 */
class RestServiceNodeRoutePartHandler extends \F3\TYPO3\Routing\FrontendNodeRoutePartHandler {

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * While matching, resolves the requested content
	 *
	 * @param string $value the complete path
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $value, $matches);

		if (!isset($matches['NodePath'])) {
			return self::MATCHRESULT_INVALIDPATH;
		}

		$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');
		if ($this->contentContext === NULL) {
			$this->contentContext = new ContentContext($workspaceName);
		}

		$workspace = $this->contentContext->getWorkspace();
		if (!$workspace) {
			return self::MATCHRESULT_NOWORKSPACE;
		}

		$pathSegments = explode('/', $matches['NodePath']);

		if (array_shift($pathSegments) !== 'sites') {
			return self::MATCHRESULT_INVALIDPATH;
		}
		$siteNodeName = array_shift($pathSegments);

		$site = $this->siteRepository->findOneByNodeName($siteNodeName);
		if (!$site) {
			return self::MATCHRESULT_NOSITE;
		}

		$this->contentContext->setCurrentSite($site);

		$siteNode = $this->contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			return self::MATCHRESULT_NOSITENODE;
		}

		$nodePath = implode('/', $pathSegments);

		$currentNode = ($nodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($nodePath);
		if (!$currentNode) {
			return self::MATCHRESULT_NOSUCHNODE;
		}

		$this->contentContext->setCurrentNode($currentNode);

		$this->value = $currentNode;
		return self::MATCHRESULT_FOUND;
	}

	/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node context path.
	 * This function load $this->value with the full node path of the given node, but strips off the leading "/".
	 *
	 * @param string $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!$value instanceof NodeInterface) {
			return FALSE;
		}
		$this->value = substr($value->getContextPath(), 1);
		return TRUE;
	}

}
?>