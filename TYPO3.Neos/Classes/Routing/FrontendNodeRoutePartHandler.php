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
 * A route part handler for finding nodes specifically in the website's frontend.
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
	 * Matches a frontend URI pointing to a node (for example a page).
	 *
	 * This function tries to find a matching node by the given relative context node path. If one was found, its
	 * absolute context node path is returned in $this->value.
	 *
	 * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
	 * in time the route part handler is invoked, the security framework is not yet fully initialized.
	 *
	 * @param string $value The relative context node path (without leading "/", relative to the current Site Node)
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		$relativeContextNodePath = $value;

		if ($relativeContextNodePath !== '') {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $relativeContextNodePath, $matches);
			if (!isset($matches['NodePath'])) {
				return self::MATCHRESULT_INVALIDPATH;
			}
			$relativeNodePath = $matches['NodePath'];
		} else {
			$relativeNodePath = '';
		}

		$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');
		$contentContext = new ContentContext($workspaceName);

		$workspace = $contentContext->getWorkspace(FALSE);
		if (!$workspace) {
			return self::MATCHRESULT_NOWORKSPACE;
		}

		$site = $contentContext->getCurrentSite();
		if (!$site) {
			return self::MATCHRESULT_NOSITE;
		}

		$siteNode = $contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			return self::MATCHRESULT_NOSITENODE;
		}

		$node = ($relativeNodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($relativeNodePath);
		if (!$node) {
			return self::MATCHRESULT_NOSUCHNODE;
		}

		$this->value = $node->getContextPath();
		return self::MATCHRESULT_FOUND;
	}

	/**
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findValueToMatch($requestPath) {
		if ($this->splitString !== '') {
			$splitStringPosition = strpos($requestPath, $this->splitString);
			if ($splitStringPosition !== FALSE) {
				$requestPath = substr($requestPath, 0, $splitStringPosition);
			}
		}
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
	 * absolute node path: /sites/footypo3org/homepage/about
	 * $this->value:       homepage/about
	 *
	 * absolute node path: /sites/footypo3org/homepage/about@user-admin
	 * $this->value:       homepage/about@user-admin

	 *
	 * @param mixed $value Either a Node object or an absolute context node path
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!$value instanceof NodeInterface && !is_string($value)) {
			return FALSE;
		}

		if (is_string($value)) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $value, $matches);
			if (!isset($matches['NodePath'])) {
				return FALSE;
			}
			$nodeContextPath = $matches['NodePath'];

			$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');
			$context = new ContentContext($workspaceName);
			$workspace = $context->getWorkspace(FALSE);
			if ($workspace === NULL) {
				return FALSE;
			}
		} else {
			$context = $value->getContext();
			$nodeContextPath = $value->getContextPath();
		}

		$siteNodePath = $context->getCurrentSiteNode()->getPath();
		if (substr($nodeContextPath, 0, strlen($siteNodePath)) !== $siteNodePath) {
			return FALSE;
		}

		$this->value = substr($nodeContextPath, strlen($siteNodePath) + 1);
		return TRUE;
	}

}
?>