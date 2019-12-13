<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;

/**
 * NOTE: This is internal only and should not be used or extended by userland code.
 *
 * @todo This should be renamed to NodeDimensionMapping or something else, because it's not just a dimension but a relation entity
 *
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="nodeData_name_value",columns={"nodedata", "name", "value"})})
 */
class NodeDimension
{
    /**
     * @ORM\ManyToOne(inversedBy="dimensions")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @var NodeData
     */
    protected $nodeData;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * @param NodeData $nodeData
     * @param string $name
     * @param string $value
     */
    public function __construct(NodeData $nodeData, $name, $value)
    {
        $this->nodeData = $nodeData;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param NodeData $nodeData
     */
    public function setNodeData($nodeData)
    {
        $this->nodeData = $nodeData;
    }

    /**
     * @return NodeData
     */
    public function getNodeData()
    {
        return $this->nodeData;
    }
}
