<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Validation\Validator\UuidValidator;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * A container of properties which can be used as a template for generating new nodes.
 *
 * @api
 */
class NodeTemplate extends AbstractNodeData
{
    /**
     * The UUID to use for the new node. Use with care.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The node name which acts as a path segment for its node path
     *
     * @var string
     */
    protected $name;

    /**
     * Allows to set a UUID to use for the node that will be created from this
     * NodeTemplate. Use with care, usually identifier generation should be left
     * to the TYPO3CR.
     *
     * @param string $identifier
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setIdentifier($identifier)
    {
        if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID "%s" given.', $identifier), 1385026112);
        }
        $this->identifier = $identifier;
    }

    /**
     * Returns the UUID set in this NodeTemplate.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set the name to $newName
     *
     * @param string $newName
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setName($newName)
    {
        if (!is_string($newName) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $newName) !== 1) {
            throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain characters, numbers and the "-" sign).', 1364290839);
        }
        $this->name = $newName;
    }

    /**
     * Get the name of this node template.
     *
     * If a name has been set using setName(), it is returned. If not, but the
     * template has a (non-empty) title property, this property is used to
     * generate a valid name. As a last resort a random name is returned (in
     * the form "name-XXXXX").
     *
     * @return string
     * @api
     */
    public function getName()
    {
        if ($this->name !== null) {
            return $this->name;
        }

        return NodePaths::generateRandomNodeName();
    }

    /**
     * A NodeTemplate is not stored in any workspace, thus this method returns NULL.
     *
     * @return void
     */
    public function getWorkspace()
    {
    }
}
