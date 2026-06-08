<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * A set of {@see WorkspaceRoleAssignment} instances
 *
 * @implements \IteratorAggregate<WorkspaceRoleAssignment>
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceRoleAssignments implements \IteratorAggregate, \Countable
{
    /**
     * @var array<WorkspaceRoleAssignment>
     */
    private array $assignments;

    private function __construct(WorkspaceRoleAssignment ...$assignments)
    {
        $this->assignments = $assignments;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function create(WorkspaceRoleAssignment ...$assignments): self
    {
        return new self(...$assignments);
    }

    /**
     * @param array<WorkspaceRoleAssignment> $assignments
     */
    public static function fromArray(array $assignments): self
    {
        return new self(...$assignments);
    }

    /**
     * Default role assignment to be specified at creation via {@see WorkspaceService::createRootWorkspace()}
     *
     * Users with the role "Neos.Neos:LivePublisher" are collaborators and everybody can read.
     */
    public static function createForLiveWorkspace(): self
    {
        return new self(
            WorkspaceRoleAssignment::createForGroup(
                'Neos.Neos:LivePublisher',
                WorkspaceRole::COLLABORATOR
            ),
            WorkspaceRoleAssignment::createForGroup(
                'Neos.Flow:Everybody',
                WorkspaceRole::VIEWER
            )
        );
    }

    /**
     * Default role assignment to be specified at creation via {@see WorkspaceService::createSharedWorkspace()}
     *
     * Users with the role "Neos.Neos:AbstractEditor" are collaborators and the specified user is manager
     */
    public static function createForSharedWorkspace(UserId $userId): self
    {
        return new self(
            WorkspaceRoleAssignment::createForUser(
                $userId,
                WorkspaceRole::MANAGER,
            ),
            WorkspaceRoleAssignment::createForGroup(
                'Neos.Neos:AbstractEditor',
                WorkspaceRole::COLLABORATOR,
            )
        );
    }

    /**
     * Default role assignment to be specified at creation via {@see WorkspaceService::createSharedWorkspace()}
     *
     * The specified user is manager
     */
    public static function createForPrivateWorkspace(UserId $userId): self
    {
        return new self(
            WorkspaceRoleAssignment::createForUser(
                $userId,
                WorkspaceRole::MANAGER,
            )
        );
    }

    public function isEmpty(): bool
    {
        return $this->assignments === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->assignments;
    }

    public function count(): int
    {
        return count($this->assignments);
    }

    public function contains(WorkspaceRoleAssignment $assignment): bool
    {
        foreach ($this->assignments as $existingAssignment) {
            if ($existingAssignment->equals($assignment)) {
                return true;
            }
        }
        return false;
    }

    public function withAssignment(WorkspaceRoleAssignment $assignment): self
    {
        return new self(...[...$this->assignments, $assignment]);
    }
}
