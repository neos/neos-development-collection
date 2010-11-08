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
 * A route part handler for the Node Service
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NodeServiceRoutePartHandler extends \F3\TYPO3\Routing\NodeRoutePartHandler {

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * While matching, resolves the requested content
	 *
	 * @param string $value the complete path
	 * @return mixed One of the MATCHRESULT_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function matchValue($value) {
		$pathSegments = explode('/', $value);

		if (count($pathSegments) < 2) {
			return self::MATCHRESULT_INVALIDPATH;
		}

		$workspaceName = array_shift($pathSegments);
		if ($this->contentContext === NULL) {
			$this->contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', $workspaceName);
		}

		$workspace = $this->contentContext->getWorkspace();
		if (!$workspace) {
			return self::MATCHRESULT_NOWORKSPACE;
		}

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
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function findValueToMatch($requestPath) {
		$lastDotPosition = strrpos($requestPath, '.');
		$lastSlashPosition = strrpos($requestPath, '/');
		if ($lastDotPosition === FALSE || $lastSlashPosition === FALSE || $lastSlashPosition > $lastDotPosition) {
			return $requestPath;
		} else {
			return substr($requestPath, 0, $lastDotPosition);
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
		$this->value = $value->getContext()->getWorkspace()->getName() . $value->getPath();
		return TRUE;
	}

}
?>