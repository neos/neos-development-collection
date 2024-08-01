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
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        public PropertyName $name,
        public string $type, // todo use \Neos\ContentRepository\Core\Infrastructure\Property\PropertyType here, and move class?
        public PropertyScope $scope, // todo move scope value object
        public int|float|string|bool|array|null $defaultValue,
        public array $metadata,
    ) {
    }

    public static function create(
        PropertyName $name,
        string $type,
        PropertyScope $scope = null,
        int|float|string|bool|array|null $defaultValue = null,
        array $metadata = null,
    ): self {
    }
}
