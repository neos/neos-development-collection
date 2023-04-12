<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\PrivilegeProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Privilege\ContentStreamPrivilege;
use Neos\ContentRepository\Core\SharedModel\Privilege\PrivilegeProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Privilege\Privileges;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIds;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * @internal
 */
final class FakePrivilegeProvider implements PrivilegeProviderInterface
{
    public function __construct(
        private readonly UserIdProviderInterface $userIdProvider,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ContentRepositoryId $contentRepositoryId,
    ) {}

    public function getPrivileges(VisibilityConstraints $visibilityConstraints): Privileges
    {
        $userId = $this->userIdProvider->getUserId();
        $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);

        $privileges = Privileges::create();

        $userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByWorkspaceOwner($userId->value);
        if ($userWorkspace === null) {
            return $privileges;
        }
        return $privileges->with(
            contentStreamPrivilege: ContentStreamPrivilege::create()->with(allowedContentStreamIds: ContentStreamIds::fromContentStreamIds($userWorkspace->currentContentStreamId))
        );
    }
}
