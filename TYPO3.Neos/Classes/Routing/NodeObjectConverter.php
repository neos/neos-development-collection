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
 * An Object Converter for Nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * This converter is registered automatically because it implements ObjectConverterInerface.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NodeObjectConverter implements \F3\FLOW3\Property\ObjectConverterInterface {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Returns a list of fully qualified class names of those classes which are supported
	 * by this property editor.
	 *
	 * @return array<string>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSupportedTypes() {
		return array('F3\TYPO3CR\Domain\Model\Node');
	}

	/**
	 * Converts the given string, array or number to a Node, including a matching context
	 *
	 * @return mixed An object or \F3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function convertFrom($source) {
		if (!is_array($source) || !isset($source['__context']) || !isset($source['__context']['nodePath']) || !isset($source['__context']['workspaceName'])) {
			return FALSE;
		}

		$pathSegments = explode('/', ltrim($source['__context']['nodePath'], '/'));

		if (count($pathSegments) < 3) {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the context path was invalid.', 1285162903);
		}

		$contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', $source['__context']['workspaceName']);
		$workspace = $contentContext->getWorkspace();
		if (!$workspace) {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the specified workspace does not exist.', 1285162905);
		}

		if (array_shift($pathSegments) !== 'sites') {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the context path was invalid.', 1285168001);
		}

		$siteNodeName = array_shift($pathSegments);
		$site = $this->siteRepository->findOneByNodeName($siteNodeName);
		if (!$site) {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the specified site does not exist.', 1285162906);
		}
		$contentContext->setCurrentSite($site);

		$siteNode = $contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the specified site node does not exist.', 1285162907);
		}

		$nodePath = implode('/', $pathSegments);

		$currentNode = ($nodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($nodePath);
		if (!$currentNode) {
			return new \F3\FLOW3\Error\Error('Could not convert array to Node object because the specified node path does not exist.', 1285162908);
		}

		$contentContext->setCurrentNode($currentNode);

		if (isset($source['properties'])) {
			// TODO Do clone
			foreach ($source['properties'] as $propertyName => $propertyValue) {
				$currentNode->setProperty($propertyName, $propertyValue);
			}
		}

		return $currentNode;
	}
}
?>