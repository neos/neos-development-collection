<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Security\Authorization\Privilege;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * TODO docs
 */
class SubtreeTagPrivilege extends AbstractPrivilege
{
    private SubtreeTag|null $subtreeTagRuntimeCache = null;
    private ContentRepositoryId|null $contentRepositoryIdRuntimeCache = null;

    private function initialize(): void
    {
        if ($this->subtreeTagRuntimeCache !== null) {
            return;
        }
        $subtreeTag = $this->getParsedMatcher();
        if (str_contains($subtreeTag, ':')) {
            [$contentRepositoryId, $subtreeTag] = explode(':', $subtreeTag);
            $this->contentRepositoryIdRuntimeCache = ContentRepositoryId::fromString($contentRepositoryId);
        }
        $this->subtreeTagRuntimeCache = SubtreeTag::fromString($subtreeTag);
    }

    /**
     * Returns true, if this privilege covers the given subject
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject): bool
    {
        if (!$subject instanceof SubtreeTagPrivilegeSubject) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s" but we got a subject of type: "%s".', self::class, SubtreeTagPrivilegeSubject::class, get_class($subject)), 1729173985);
        }
        $contentRepositoryId = $this->getContentRepositoryId();
        if ($contentRepositoryId !== null && $subject->contentRepositoryId !== null && !$contentRepositoryId->equals($subject->contentRepositoryId)) {
            return false;
        }
        return $subject->subTreeTag->equals($this->getSubtreeTag());
    }

    public function getSubtreeTag(): SubtreeTag
    {
        $this->initialize();
        assert($this->subtreeTagRuntimeCache !== null);
        return $this->subtreeTagRuntimeCache;
    }

    public function getContentRepositoryId(): ?ContentRepositoryId
    {
        $this->initialize();
        return $this->contentRepositoryIdRuntimeCache;
    }
}
