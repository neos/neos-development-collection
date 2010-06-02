<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Version;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A Version object wraps an nt:version node. It provides convenient access to
 * version information.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Version extends \F3\TYPO3CR\Node implements \F3\PHPCR\Version\VersionInterface {

	/**
	 *
	 * @param array $rawData
	 * @param \F3\PHPCR\SessionInterface $session
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @param \F3\PHPCR\NodeInterface $node 
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 * @todo: Frozen node should not have a parent node in any workspace, however this is not allowed currently.
	 */
	public function __construct(array $rawData = array(), \F3\PHPCR\SessionInterface $session, \F3\FLOW3\Object\ObjectManagerInterface $objectManager, \F3\PHPCR\NodeInterface $node = NULL) {
		parent::__construct($rawData, $session, $objectManager);

		if ($node !== NULL) {
			$created = new \DateTime();
			$this->name = 'versionOf' . $node->getName() . '@' . $created->format(\DATE_ISO8601);
			$this->setProperty('jcr:created', $created);
			$this->createVersionFromNode($node);
		}
	}

	/**
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createFrozenNodeForNode(\F3\PHPCR\NodeInterface $node) {
		$frozenNode = $this->addNode('jcr:frozenNode', 'nt:frozenNode');

		$frozenPrimaryType = $node->getProperty('jcr:primaryType')->getValue()->getString();
		$frozenNode->setProperty('jcr:frozenPrimaryType', $frozenPrimaryType, \F3\PHPCR\PropertyType::STRING);

		$frozenMixinTypes = $node->getMixinNodeTypes();
		$frozenMixinTypeNames = array();
		foreach ($frozenMixinTypes as $frozenMixinType) {
			$frozenMixinTypeNames[] = $frozenMixinType->getName();
		}
		$frozenNode->setProperty('jcr:frozenMixinTypes', $frozenMixinTypeNames);

		$frozenUuid = $node->getProperty('jcr:uuid')->getValue()->getString();
		$frozenNode->setProperty('jcr:frozenUuid', $frozenUuid, \F3\PHPCR\PropertyType::STRING);

		$properties = $node->getProperties();
		$filterPropertyNames = array('jcr:primaryType', 'jcr:mixinTypes', 'jcr:uuid');
		for ($i=0; $i < $properties->getSize(); $i++) {
			$property = $properties->current();
			$propertyName = $property->getName();
			if (in_array($propertyName, $filterPropertyNames) === FALSE) {
					// @todo check if OPV of property is COPY or VERSION
					// @todo make setProperty accept Value instances and use that!
				$frozenNode->setProperty($property->getName(), $property->getValue()->getString(), $property->getType());
			}

			if ($properties->hasNext() === TRUE) {
				$properties->next();
			}
		}

		$childNodes = $node->getNodes();
		while ($childNodes->valid()) {
			$childNode = $childNodes->nextNode();
				// @todo: check if OPV of child node is COPY or VERSION
			//$this->session->getWorkspace()->copy($childNode->getPath(), $frozenNode->getPath() . '/' . $childNode->getName(), $this->session->getWorkspace());
		}
	}

	/**
	 * Returns the VersionHistory that contains this Version
	 *
	 * @return \F3\PHPCR\Version\VersionHistoryInterface the VersionHistory that contains this Version
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 */
	public function getContainingHistory(){
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322108);
	}

	/**
	 * Returns the date this version was created. This corresponds to the
	 * value of the jcr:created property in the nt:version node that represents
	 * this version.
	 *
	 * @return \DateTime
	 * @throws \F3\PHPCR\RepositoryException - if an error occurs
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 */
	public function getCreated(){
		return $this->getProperty('jcr:created');
	}

	/**
	 * Assuming that this Version object was acquired through a Workspace W and
	 * is within the VersionHistory H, this method returns the successor of this
	 * version along the same line of descent as is returned by
	 * H.getAllLinearVersions() where H was also acquired through W.
	 *
	 * Note that under simple versioning the behavior of this method is equivalent
	 * to getting the unique successor (if any) of this version.
	 *
	 * @see VersionHistory#getAllLinearVersions()
	 * @return \F3\PHPCR\VersionInterface a Version or NULL if no linear successor exists.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getLinearSuccessor(){
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322110);
	}

	/**
	 * Returns the successor versions of this version. This corresponds to
	 * returning all the nt:version nodes referenced by the jcr:successors
	 * multi-value property in the nt:version node that represents this version.
	 *
	 * @return array of \F3\PHPCR\Version\VersionInterface
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 */
	public function getSuccessors(){
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322111);
	}

	/**
	 * Assuming that this Version object was acquired through a Workspace W and
	 * is within the VersionHistory H, this method returns the predecessor of
	 * this version along the same line of descent as is returned by
	 * H.getAllLinearVersions() where H was also acquired through W.
	 *
	 * Note that under simple versioning the behavior of this method is equivalent
	 * to getting the unique predecessor (if any) of this version.
	 *
	 * @see VersionHistory#getAllLinearVersions()
	 * @return \F3\PHPCR\Version\VersionInterface a Version or NULL if no linear predecessor exists.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getLinearPredecessor(){
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322112);
	}

	/**
	 * In both simple and full versioning repositories, this method returns the
	 * predecessor versions of this version. This corresponds to returning all
	 * the nt:version nodes whose jcr:successors property includes a reference
	 * to the nt:version node that represents this version.
	 *
	 * @return array of \F3\PHPCR\Version\VersionInterface
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 */
	public function getPredecessors(){
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322113);
	}

	/**
	 * Returns the frozen node of this version.
	 *
	 * @return \F3\PHPCR\NodeInterface a Node object
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFrozenNode(){
		return $this->getNode('jcr:frozenNode');
	}

}
?>
