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
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Annotations as Flow;

/**
 * Publish a set of nodes in a workspace
 *
 * @Flow\Proxy(false)
 */
final class PublishIndividualNodesFromWorkspace
{
    private WorkspaceName $workspaceName;

    /**
     * @var array|NodeAddress[]
     */
    private array $nodeAddresses;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * @param WorkspaceName $workspaceName
     * @param array|NodeAddress[] $nodeAddresses
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(WorkspaceName $workspaceName, array $nodeAddresses, UserIdentifier $initiatingUserIdentifier)
    {
        $this->workspaceName = $workspaceName;
        $this->nodeAddresses = $nodeAddresses;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return array|NodeAddress[]
     */
    public function getNodeAddresses(): array
    {
        return $this->nodeAddresses;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
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

        return new self(
            new WorkspaceName($array['workspaceName']),
            $nodeAddresses,
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }
}
