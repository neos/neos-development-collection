<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface as LegacyNodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Intermediary\Domain\Exception\NodeImplementationClassNameIsInvalid;
use Neos\Flow\Annotations as Flow;

/**
 * The node implementation class name resolver
 *
 * @Flow\Proxy(false)
 */
final class NodeImplementationClassName
{
    /**
     * @throws NodeImplementationClassNameIsInvalid
     */
    public static function forNodeType(NodeType $nodeType): string
    {
        $customClassName = $nodeType->getConfiguration('class');
        if (!empty($customClassName)) {
            if (!class_exists($customClassName)) {
                throw NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotExist($customClassName);
            }

            $implementedInterfaces = class_implements($customClassName);
            if (!in_array(NodeBasedReadModelInterface::class, $implementedInterfaces)) {
                if (in_array(LegacyNodeInterface::class, $implementedInterfaces)) {
                    throw NodeImplementationClassNameIsInvalid::becauseTheClassImplementsTheDeprecatedLegacyInterface($customClassName);
                }
                throw NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotImplementTheRequiredInterface($customClassName);
            }

            return $customClassName;
        } else {
            return TraversableNode::class;
        }
    }
}
