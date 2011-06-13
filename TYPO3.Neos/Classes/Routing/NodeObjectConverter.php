<?php
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
use \F3\TYPO3CR\Domain\Model\NodeInterface;

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
	 * Converts the specified node path into a Node.
	 *
	 * The node path must be an absolute context node path and can be specified as a string or as an array item with the
	 * key "__contextNodePath". The latter case is for updating existing nodes.
	 *
	 * This conversion method does not support / allow creation of new nodes because new nodes should be created through
	 * the createNode() method of an existing reference node.
	 *
	 * Also note that the context's "current node" is not affected by this object converter, you will need to set it to
	 * whatever node your "current" node is, if any.
	 *
	 * @param string|array $source Either a string or array containing the absolute context node path which identifies the node. For example "/sites/mysitecom/homepage/about@user-admin"
	 * @param string $targetType not used
	 * @param array $subProperties not used
	 * @param \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration not used
	 * @return mixed An object or \F3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $subProperties = array(), \F3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_string($source)) {
			$source = array('__contextNodePath' => $source);
		}

		if (!is_array($source) || !isset($source['__contextNodePath'])) {
			return new Error('Could not convert ' . gettype($source) . ' to Node object, a valid absolute context node path as a string or array is expected.', 1302879936);
		}

		preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $source['__contextNodePath'], $matches);
		if (!isset($matches['NodePath'])) {
			return new Error('Could not convert array to Node object because the node path was invalid.', 1285162903);
		}
		$nodePath = $matches['NodePath'];
		$workspaceName = (isset($matches['WorkspaceName']) ? $matches['WorkspaceName'] : 'live');

		$contentContext = new ContentContext($workspaceName);

		$workspace = $contentContext->getWorkspace(FALSE);
		if (!$workspace) {
			return new Error(sprintf('Could not convert %s to Node object because the workspace "%s" as specified in the context node path does not exist.', $source['__contextNodePath'], $workspaceName), 1285162905);
		}

		$node = $contentContext->getNode($nodePath);
		if (!$node) {
			return new Error(sprintf('Could not convert array to Node object because the node "%s" does not exist.', $nodePath), 1285162908);
		}

		$nodeToReturn = $node;
		foreach ($source as $nodePropertyKey => $nodePropertyValue) {
			if (substr($nodePropertyKey, 0, 2) === '__') {
				continue;
			}
			switch ($nodePropertyKey) {
				case 'properties' :
					if (isset($source['properties']) && count($source['properties']) > 0) {
						$nodeToReturn = clone $node;
						foreach ($source['properties'] as $propertyName => $propertyValue) {
							$nodeToReturn->setProperty($propertyName, $propertyValue);
						}
					}
				break;
				default :
					return new Error(sprintf('Specified unsupported node property "%s" while converting "%s" into a Node object.', $nodePropertyKey, $nodePath), 1303126141);
			}
		}
		return $nodeToReturn;
	}
}
?>