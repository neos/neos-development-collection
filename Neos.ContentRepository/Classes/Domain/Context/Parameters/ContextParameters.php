<?php
namespace Neos\ContentRepository\Domain\Context\Parameters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Policy\Role;

/**
 * The context parameters value object
 */
final class ContextParameters
{
    /**
     * @var \DateTimeImmutable
     */
    protected $currentDateTime;

    /**
     * @var array|Role[]
     */
    protected $roles = [];

    /**
     * @var boolean
     * @deprecated use roles instead
     */
    protected $invisibleContentShown = false;

    /**
     * @var boolean
     * @deprecated evaluate roles instead
     */
    protected $removedContentShown = false;

    /**
     * @var boolean
     * @deprecated evaluate roles instead
     */
    protected $inaccessibleContentShown = false;


    public function __construct(\DateTimeImmutable $currentDateTime, array $roles, bool $invisibleContentShown, bool $removedContentShown, bool $inaccessibleContentShown)
    {
        $this->currentDateTime = $currentDateTime;
        $this->roles = $roles;
        $this->invisibleContentShown = $invisibleContentShown;
        $this->removedContentShown = $removedContentShown;
        $this->inaccessibleContentShown = $invisibleContentShown;
    }


    /**
     * @return \DateTimeImmutable
     */
    public function getCurrentDateTime(): \DateTimeImmutable
    {
        return $this->currentDateTime;
    }

    /**
     * @return array|Role[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return bool
     * @deprecated evaluate roles instead
     */
    public function isInvisibleContentShown(): bool
    {
        return $this->invisibleContentShown;
    }

    /**
     * @return bool
     * @deprecated evaluate roles instead
     */
    public function isRemovedContentShown(): bool
    {
        return $this->removedContentShown;
    }

    /**
     * @return bool
     * @deprecated evaluate roles instead
     */
    public function isInaccessibleContentShown(): bool
    {
        return $this->inaccessibleContentShown;
    }
}
