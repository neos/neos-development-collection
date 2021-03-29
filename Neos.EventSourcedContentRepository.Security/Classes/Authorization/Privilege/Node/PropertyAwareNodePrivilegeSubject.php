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

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * A node privilege subject which can restricted to a single node property
 */
class PropertyAwareNodePrivilegeSubject extends NodePrivilegeSubject
{
    /**
     * @var NodeBasedReadModelInterface
     */
    protected $node;

    /**
     * @var string
     */
    protected $propertyName = null;

    /**
     * @var JoinPointInterface
     */
    protected $joinPoint = null;

    /**
     * @param NodeBasedReadModelInterface $node
     * @param JoinPointInterface $joinPoint
     * @param string $propertyName
     */
    public function __construct(NodeBasedReadModelInterface $node, JoinPointInterface $joinPoint = null, $propertyName = null)
    {
        $this->propertyName = $propertyName;
        parent::__construct($node, $joinPoint);
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return boolean
     */
    public function hasPropertyName()
    {
        return $this->propertyName !== null;
    }
}
