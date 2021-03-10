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

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Exception\NodeConfigurationException;
use Neos\ContentRepository\Intermediary\Domain\Exception\PropertyCollectionImplementationClassNameIsInvalid;
use Neos\Flow\Annotations as Flow;

/**
 * The property collection implementation class name resolver
 *
 * @Flow\Proxy(false)
 */
final class PropertyCollectionImplementationClassName
{
    /**
     * @throws PropertyCollectionImplementationClassNameIsInvalid
     */
    public static function forNodeType(NodeType $nodeType): string
    {
        $customClassName = $nodeType->getConfiguration('propertyCollectionClass');
        if (!empty($customClassName)) {
            if (!class_exists($customClassName)) {
                throw PropertyCollectionImplementationClassNameIsInvalid::becauseTheClassDoesNotExist($customClassName);
            }

            if (!in_array(PropertyCollectionInterface::class, class_implements($customClassName))) {
                throw PropertyCollectionImplementationClassNameIsInvalid::becauseTheClassDoesNotImplementTheRequiredInterface($customClassName);
            }

            return $customClassName;
        } else {
            return PropertyCollection::class;
        }
    }
}
