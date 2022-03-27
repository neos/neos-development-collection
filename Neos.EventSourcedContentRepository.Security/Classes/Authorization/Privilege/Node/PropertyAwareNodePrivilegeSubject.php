<?php
namespace Neos\EventSourcedContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * A node privilege subject which can restricted to a single node property
 */
class PropertyAwareNodePrivilegeSubject extends NodePrivilegeSubject
{
    protected ?string $propertyName = null;

    public function __construct(
        NodeInterface $node,
        ?JoinPointInterface $joinPoint = null,
        ?string $propertyName = null
    ) {
        $this->propertyName = $propertyName;
        parent::__construct($node, $joinPoint);
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function hasPropertyName(): bool
    {
        return $this->propertyName !== null;
    }
}
