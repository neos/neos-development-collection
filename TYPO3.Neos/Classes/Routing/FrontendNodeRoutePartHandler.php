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
 * A route part handler for finding nodes specifically in the frontend context.
 * This handler (currently) only supports the "live" workspace.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope singleton
 */
class FrontendNodeRoutePartHandler extends \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart {

	const MATCHRESULT_FOUND = TRUE;
	const MATCHRESULT_NOWORKSPACE = -1;
	const MATCHRESULT_NOSITE = -2;
	const MATCHRESULT_NOSITENODE = -3;
	const MATCHRESULT_NOSUCHNODE = -4;
	const MATCHRESULT_NOSUCHCONTENT = -5;
	const MATCHRESULT_INVALIDPATH = -6;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * Matches a frontend URI pointing to a node (for example a page).
	 * This function assumes that the given node path is relative to the "current site node path".
	 *
	 * @param string $value Node path (without leading "/"), relative to the site node
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		if ($value !== '') {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $value, $matches);
			if (!isset($matches['NodePath'])) {
				return self::MATCHRESULT_INVALIDPATH;
			}
			$relativeNodePath = $matches['NodePath'];
		} else {
			$relativeNodePath = '';
		}

		$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');
		if ($this->contentContext === NULL) {
			$this->contentContext = new ContentContext($workspaceName);
		}

		$workspace = $this->contentContext->getWorkspace(FALSE);
		if (!$workspace) {
			return self::MATCHRESULT_NOWORKSPACE;
		}

		$site = $this->contentContext->getCurrentSite();
		if (!$site) {
			return self::MATCHRESULT_NOSITE;
		}

		$siteNode = $this->contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			return self::MATCHRESULT_NOSITENODE;
		}

		$currentNode = ($relativeNodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($relativeNodePath);
		if (!$currentNode) {
			return self::MATCHRESULT_NOSUCHNODE;
		}

		$this->contentContext->setCurrentNode($currentNode);

		$this->value = $currentNode;
		return self::MATCHRESULT_FOUND;
	}

	/**
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function findValueToMatch($requestPath) {
		if (strpos($requestPath, '.') === FALSE) {
			return $requestPath;
		} else {
			$splitRequestPath = explode('/', $requestPath);
			$lastPart = array_pop($splitRequestPath);
			$dotPosition = strpos($lastPart, '.');
			if ($dotPosition !== FALSE) {
				$lastPart = substr($lastPart, 0, $dotPosition);
			}
			array_push($splitRequestPath, $lastPart);
			return implode('/', $splitRequestPath);
		}
	}

	/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node context path.
	 *
	 * In order to render a suitable frontend URI, this function strips off the path to the site node and only keeps
	 * the actual node path relative to that site node. In practice this function would set $this->value as follows:
	 *
	 * full node path: /sites/footypo3org/homepage/about
	 * $this->value:   homepage/about
	 *
	 * @param string $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!$value instanceof NodeInterface) {
			return FALSE;
		}
		$siteNodePath = $value->getContext()->getCurrentSiteNode()->getPath();
		$nodeContextPath = $value->getContextPath();

		if (substr($nodeContextPath, 0, strlen($siteNodePath)) !== $siteNodePath) {
			return FALSE;
		}

		$this->value = substr($nodeContextPath, strlen($siteNodePath) + 1);
		return TRUE;
	}

}
?>