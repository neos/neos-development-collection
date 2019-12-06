<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;

/**
 * Discard a set of nodes in a workspace
 */
final class DiscardIndividualNodesFromWorkspace
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * @var NodeAddress[]
     */
    private $nodeAddresses;

    /**
     * PublishIndividualNodesInWorkspace constructor.
     * @param WorkspaceName $workspaceName
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress[] $nodeAddresses
     */
    public function __construct(WorkspaceName $workspaceName, array $nodeAddresses)
    {
        $this->workspaceName = $workspaceName;
        $this->nodeAddresses = $nodeAddresses;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress[]
     */
    public function getNodeAddresses(): array
    {
        return $this->nodeAddresses;
    }

    public static function fromArray(array $array): self
    {
        $nodeAddresses = [];
        foreach ($array['nodeAddresses'] as $nodeAddressArray) {
            $nodeAddresses[] = new NodeAddress(
                ContentStreamIdentifier::fromString($nodeAddressArray['contentStreamIdentifier']),
                new DimensionSpacePoint($nodeAddressArray['dimensionSpacePoint']),
                NodeAggregateIdentifier::fromString($nodeAddressArray['nodeAggregateIdentifier']),
                null
            );
        }
        return new static(
            new WorkspaceName($array['workspaceName']),
            $nodeAddresses
        );
    }
}
