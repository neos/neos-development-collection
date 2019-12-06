<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Integrity\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;

final class AddMissingTetheredNodes
{
    /**
     * The node type to fix
     *
     * @var NodeType
     */
    private $nodeType;

    /**
     * Name of the tethered node(s) to fix
     *
     * @var NodeName;
     */
    private $tetheredNodeName;

    public function __construct(NodeType $nodeType, NodeName $tetheredNodeName)
    {
        $this->nodeType = $nodeType;
        $this->tetheredNodeName = $tetheredNodeName;
    }

    public function getNodeType(): NodeType
    {
        return $this->nodeType;
    }

    public function getTetheredNodeName(): NodeName
    {
        return $this->tetheredNodeName;
    }
}
