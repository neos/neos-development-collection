<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * Filter nodes having the given property and its value not empty.
 */
class PropertyNotEmpty implements NodeBasedFilterInterface
{
    /**
     * The property name to be checked for non-empty value
     */
    protected string $propertyName;

    /**
     * Sets the property name to be checked for non-empty value.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function matches(NodeInterface $node): bool
    {
        if ($node->hasProperty($this->propertyName)) {
            $propertyValue = $node->getProperty($this->propertyName);
            return !empty($propertyValue);
        }
        return false;
    }
}
