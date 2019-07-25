<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeTypeNotFound;

trait NodeRetyping
{
    /**
     * @param ChangeNodeAggregateType $command
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleChangeNodeAggregateType(ChangeNodeAggregateType $command)
    {
        throw new \DomainException('Changing the type of a node aggregate is not yet supported', 1554555107);
        $this->readSideMemoryCacheManager->disableCache();

        /*
        if (!$this->nodeTypeManager->hasNodeType((string)$command->getNewNodeTypeName())) {
            throw new NodeTypeNotFound('The given node type "' . $command->getNewNodeTypeName() . '" is unknown to the node type manager', 1520009174);
        }

        $this->checkConstraintsImposedByAncestors($command);
        $this->checkConstraintsImposedOnAlreadyPresentDescendants($command);
*/
        // TODO: continue implementing!
    }
}
