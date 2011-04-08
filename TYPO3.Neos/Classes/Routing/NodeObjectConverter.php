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

use \F3\FLOW3\Error\Error;
use \F3\TYPO3\Domain\Service\ContentContext;

/**
 * An Object Converter for Nodes which can be used for routing (but also for other
 * purposes) as a plugin for the Property Mapper.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope singleton
 */
class NodeObjectConverter extends \F3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	protected $sourceTypes = array('string', 'array');
	protected $targetType = 'F3\TYPO3CR\Domain\Model\NodeInterface';
	protected $priority = 1;

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
	 * Converts the given string, array or number to a Node, including a matching context
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $subProperties
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return mixed An object or \F3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $subProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_string($source)) {
			$source = array('__nodePath' => $source);
		}

		if (!is_array($source) || !isset($source['__nodePath'])) {
			return FALSE;
		}

		$pathSegments = explode('/', ltrim($source['__nodePath'], '/'));

		if (count($pathSegments) < 2) {
			return new Error('Could not convert array to Node object because the node path was invalid. The path must have at least two parts: /sites/[pathToNode]', 1285162903);
		}

		if (array_shift($pathSegments) !== 'sites') {
			return new Error('Could not convert array to Node object because the node path was invalid. The first segment was not "sites"', 1285168001);
		}

		$workspaceName = $this->securityContext->getParty()->getPreferences()->get('context.workspace');
		$contentContext = new ContentContext($workspaceName);
		$workspace = $contentContext->getWorkspace(FALSE);
		if (!$workspace) {
			return new Error('Could not convert array to Node object because the specified workspace does not exist.', 1285162905);
		}


		$siteNodeName = array_shift($pathSegments);
		$site = $this->siteRepository->findOneByNodeName($siteNodeName);
		if (!$site) {
			return new Error('Could not convert array to Node object because the specified site does not exist.', 1285162906);
		}
		$contentContext->setCurrentSite($site);

		$siteNode = $contentContext->getCurrentSiteNode();
		if (!$siteNode) {
			return new Error('Could not convert array to Node object because the specified site node does not exist.', 1285162907);
		}

		if (count($pathSegments) === 0) {
			return $siteNode;
		}

		$nodePath = implode('/', $pathSegments);

		$currentNode = ($nodePath === '') ? $siteNode->getPrimaryChildNode() : $siteNode->getNode($nodePath);
		if (!$currentNode) {
			return new Error('Could not convert array to Node object because the specified node path does not exist.', 1285162908);
		}

		$contentContext->setCurrentNode($currentNode);

		if (isset($source['properties'])) {
				// FIXME Do clone! See also \F3\TYPO3\Service\ExtDirect\V1\Controller\NodeController::updateAction()
			foreach ($source['properties'] as $propertyName => $propertyValue) {
				$currentNode->setProperty($propertyName, $propertyValue);
			}
		}

		return $currentNode;
	}
}
?>