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
 * A route part handler for nodes
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NodeRoutePartHandler extends \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart {

	const MATCHRESULT_FOUND = TRUE;
	const MATCHRESULT_NOWORKSPACE = -1;
	const MATCHRESULT_NOSITE = -2;
	const MATCHRESULT_NOSITENODE = -3;
	const MATCHRESULT_NOSUCHNODE = -4;
	const MATCHRESULT_NOSUCHCONTENT = -5;
	const MATCHRESULT_INVALIDPATH = -6;

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
	 * While matching, resolves the requested content
	 *
	 * @param string $value the complete path
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		if ($this->contentContext === NULL) {
			$this->contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'live');
		}

		$workspace = $this->contentContext->getWorkspace();
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

		$currentNode = ($value === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($value);
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
	 * Checks, whether given value can be resolved and if so, sets $this->value to the resolved value.
	 * If $value is empty, this method checks whether a default value exists.
	 *
	 * @param string $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!$value instanceof \F3\TYPO3CR\Domain\Model\Node) {
			return FALSE;
		}
		$this->value = substr($value->getPath(), strlen($value->getContext()->getCurrentSiteNode()->getPath()) + 1);
		return TRUE;
	}

}
?>