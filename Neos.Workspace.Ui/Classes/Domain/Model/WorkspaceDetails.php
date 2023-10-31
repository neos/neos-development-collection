<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\User;

//#[Flow\Entity]
class WorkspaceDetails
{
//    /**
//     * @ORM\Column(nullable=true)
//     * @var \DateTime | null
//     */
//    protected $lastChangedDate;
//
//    /**
//     * @ORM\Column(nullable=true)
//     * @var string | null
//     */
//    protected $lastChangedBy;
//
//    /**
//     * @ORM\OneToOne
//     * @Flow\Identity
//     * @var Workspace
//     */
//    protected $workspace;
//
//    /**
//     * @ORM\Column(nullable=true)
//     * @var string | null
//     */
//    protected $creator;
//
//    /**
//     * @ORM\ManyToMany
//     * @var ArrayCollection<User>
//     */
//    protected $acl = [];
//
//    public function __construct(
//        Workspace $workspace,
//        string $creator = null,
//        \DateTime $lastChangedDate = null,
//        string $lastChangedBy = null,
//        ArrayCollection $acl = null
//    ) {
//        $this->workspace = $workspace;
//        $this->creator = $creator;
//        $this->lastChangedDate = $lastChangedDate ?? new \DateTime();
//        $this->lastChangedBy = $lastChangedBy ?? $creator;
//        $this->acl = $acl ?? new ArrayCollection();
//    }
//
//    public function getLastChangedDate(): ?\DateTime
//    {
//        return $this->lastChangedDate;
//    }
//
//    public function setLastChangedDate(?\DateTime $lastChangedDate): void
//    {
//        $this->lastChangedDate = $lastChangedDate;
//    }
//
//    public function getLastChangedBy(): ?string
//    {
//        return $this->lastChangedBy;
//    }
//
//    public function setLastChangedBy(?string $lastChangedBy): void
//    {
//        $this->lastChangedBy = $lastChangedBy;
//    }
//
//    public function getWorkspace(): Workspace
//    {
//        return $this->workspace;
//    }
//
//    public function getCreator(): ?string
//    {
//        return $this->creator;
//    }
//
//    public function setCreator(?string $creator): void
//    {
//        $this->creator = $creator;
//    }
//
//    /**
//     * @param User[] $acl
//     */
//    public function setAcl(array $acl): void
//    {
//        $this->acl = $acl;
//    }
//
//    /**
//     * @return User[]
//     */
//    public function getAcl(): array
//    {
//        return $this->acl instanceof Collection ? $this->acl->toArray() : $this->acl;
//    }
//
}
