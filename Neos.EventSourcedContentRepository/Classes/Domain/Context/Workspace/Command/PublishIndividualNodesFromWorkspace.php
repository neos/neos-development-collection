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

use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress;

/**
 * Publish a set of nodes in a workspace
 */
final class PublishIndividualNodesFromWorkspace
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
     * @param NodeAddress[] $nodeAddresses
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
     * @return NodeAddress[]
     */
    public function getNodeAddresses(): array
    {
        return $this->nodeAddresses;
    }
}
