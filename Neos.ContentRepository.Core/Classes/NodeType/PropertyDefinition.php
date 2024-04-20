<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @api
 */
final readonly class PropertyDefinition
{
    /**
     * @param int|float|string|bool|array<int|string,mixed>|null $defaultValue
     */
    public function __construct(
        public PropertyName $name,
        public string $type,
        public PropertyScope $scope,
        public int|float|string|bool|array|null $defaultValue,
        public array $metadata,
    ) {
    }
}
